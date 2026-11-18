<?php

namespace App\Http\Controllers;

use App\Models\KasTransaction;
use App\Models\DuesPayment;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\AcademicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    /**
     * Primary AI Provider: Groq API Models
     */
    private $groqModels = [
        'openai/gpt-oss-120b',
        'qwen/qwen3.8-27b',
        'openai/gpt-oss-20b',
    ];

    /**
     * Backup AI Provider: OpenRouter API Models
     */
    private $openrouterModels = [
        'openrouter/free',
        'google/gemma-4-31b-it:free',
        'qwen/qwen3.8-27b:free',
        'meta-llama/llama-3.3-70b-instruct:free',
    ];

    /**
     * Besaran iuran kas mingguan standar per mahasiswa (Rp)
     */
    private const DUES_RATE_PER_WEEK = 5000;

    /**
     * Kirim pesan ke AI Chatbot
     * - Two-Tier Action Extractor: Regex Cepat + AI Intent Parser
     * - Eksekusi CRUD Langsung ke Database (Tersinkronisasi Real-Time untuk Semua Pengguna)
     * - Dual-Provider AI: Groq Primary -> OpenRouter Backup
     * - Hak Akses Ketat per Role (Admin/Ketua: Akun & Semester, Bendahara: Kas, Mahasiswa: Read-Only)
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'message.required' => 'Pesan chatbot tidak boleh kosong.',
            'message.max' => 'Pesan chatbot terlalu panjang (maksimal 1.000 karakter).',
        ]);

        $user = $request->user() ?: auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'reply' => 'Sesi login Anda telah berakhir atau belum terautentikasi. Silakan masuk kembali.',
            ], 401);
        }

        $userMessage = trim((string) $request->input('message', ''));

        if (empty($userMessage)) {
            return response()->json([
                'success' => false,
                'reply' => 'Pesan tidak boleh kosong.',
            ], 422);
        }

        // 0. Cek Status Pemblokiran Akses Chatbot Pengguna (Kena Sanksi 3x Toxic/Vulgar)
        $blockKey = 'chatbot_blocked_user_' . $user->id;
        if (Cache::has($blockKey)) {
            $blockUntil = (int) Cache::get($blockKey);
            $remainingSeconds = max(1, $blockUntil - time());
            $remainingMinutes = ceil($remainingSeconds / 60);

            AuditLog::record(
                'chatbot_akses_ditolak',
                "Pengguna {$user->name} ({$user->npm}) mencoba mengirim chat saat status akses chatbot sedang diblokir ({$remainingSeconds} detik tersisa)",
                $user,
                'chatbot',
                ['remaining_seconds' => $remainingSeconds]
            );

            return response()->json([
                'success' => false,
                'is_blocked' => true,
                'remaining_seconds' => $remainingSeconds,
                'reply' => "🚫 **AKSES CHATBOT DIBLOKIR SEMENTARA**\n\n"
                    . "Akun Anda (**{$user->name}**) saat ini sedang diblokir sementara dari layanan AI Chatbot karena telah mencapai batas 3 kali mengirimkan pesan kasar, toxic, atau vulgar.\n\n"
                    . "⏱️ **Sisa Waktu Pemblokiran**: **{$remainingMinutes} menit** ({$remainingSeconds} detik).\n"
                    . "Sistem akan terbuka kembali secara otomatis setelah masa hukuman berakhir. Harap gunakan bahasa yang beretika, sopan, dan santun demi kenyamanan bersama.",
                'action_executed' => false,
                'is_confirmation' => false,
            ], 403);
        }

        // 1. Guardrail Keamanan & Etika: Privasi Kredensial & Moderasi Toxic/Vulgar (Batas 3x Blokir Sementara)
        $moderationResult = $this->handleModerationGuardrails($userMessage, $user);
        if ($moderationResult !== null) {
            $httpStatus = $moderationResult['http_status'] ?? 200;
            unset($moderationResult['http_status']);
            return response()->json($moderationResult, $httpStatus);
        }

        // 2. Deteksi dan Eksekusi Aksi CRUD dengan Sistem Konfirmasi Ketat
        $actionResult = $this->detectAndExecuteAction($userMessage, $user);
        
        if ($actionResult !== null) {
            $replyText = is_array($actionResult) ? ($actionResult['reply'] ?? '') : (string) $actionResult;
            $isConfirmation = is_array($actionResult) ? ($actionResult['is_confirmation'] ?? false) : false;
            $isSuccess = str_starts_with(trim($replyText), '✅');

            // Simpan riwayat interaksi ke session
            $history = session('chatbot_history', []);
            $history[] = ['role' => 'user', 'content' => $userMessage];
            $history[] = ['role' => 'assistant', 'content' => $replyText];
            if (count($history) > 12) {
                $history = array_slice($history, -12);
            }
            session(['chatbot_history' => $history]);

            return response()->json([
                'success' => true,
                'reply' => $replyText,
                'action_executed' => $isSuccess,
                'is_confirmation' => $isConfirmation,
            ]);
        }

        // 2.5 Deteksi Pertanyaan Panduan Posisi Tombol, Format Perintah, & Info Panduan
        $buttonResult = $this->resolveButtonPlacement($userMessage, $user);
        if ($buttonResult !== null) {
            $history = session('chatbot_history', []);
            $history[] = ['role' => 'user', 'content' => $userMessage];
            $history[] = ['role' => 'assistant', 'content' => $buttonResult];
            if (count($history) > 12) {
                $history = array_slice($history, -12);
            }
            session(['chatbot_history' => $history]);

            return response()->json([
                'success' => true,
                'reply' => $buttonResult,
                'action_executed' => false,
                'is_confirmation' => false,
            ]);
        }

        // 3. Bangun konteks lengkap data web real-time untuk AI
        $systemContext = $this->buildSystemContext($user);

        // 4. Siapkan riwayat percakapan dari session (maks 6 pesan terakhir)
        $history = session('chatbot_history', []);

        $messages = [];
        $messages[] = [
            'role' => 'system',
            'content' => $systemContext,
        ];

        foreach ($history as $h) {
            $messages[] = $h;
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        // 5. Panggil AI dengan sistem Dual-Provider (Groq Primary -> OpenRouter Backup)
        $aiResult = $this->callAiWithFallback($messages, 0.5, 600);

        if ($aiResult['success']) {
            $aiReply = $aiResult['reply'];

            // Simpan riwayat percakapan asli
            $history[] = ['role' => 'user', 'content' => $request->input('message')];
            $history[] = ['role' => 'assistant', 'content' => $aiReply];

            if (count($history) > 12) {
                $history = array_slice($history, -12);
            }

            session(['chatbot_history' => $history]);

            return response()->json([
                'success' => true,
                'reply' => $aiReply,
                'provider' => $aiResult['provider'],
                'action_executed' => false,
            ]);
        } else {
            // Ketika kuota token AI harian habis atau rate limit: Tampilkan notifikasi ramah & solutif
            $friendlyReply = $this->generateFriendlyTokenExhaustedNotice($user, $userMessage);

            $history[] = ['role' => 'user', 'content' => $request->input('message')];
            $history[] = ['role' => 'assistant', 'content' => $friendlyReply];
            if (count($history) > 12) {
                $history = array_slice($history, -12);
            }
            session(['chatbot_history' => $history]);

            return response()->json([
                'success' => true,
                'is_token_exhausted' => true,
                'reply' => $friendlyReply,
                'action_executed' => false,
            ]);
        }
    }

    /**
     * Reset riwayat percakapan chatbot
     */
    public function clearHistory()
    {
        session()->forget('chatbot_history');
        session()->forget('chatbot_pending_action');

        return response()->json([
            'success' => true,
            'message' => 'Riwayat percakapan berhasil dihapus.',
        ]);
    }

    /**
     * Panggil AI dengan fallback chain: Groq -> OpenRouter
     */
    private function callAiWithFallback(array $messages, float $temperature = 0.3, int $maxTokens = 600): array
    {
        $lastError = '';

        // 1. Primary: Groq API
        $groqKey = env('GROQ_API_KEY');
        if (!empty($groqKey)) {
            foreach ($this->groqModels as $model) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $groqKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                        'temperature' => $temperature,
                        'max_tokens' => $maxTokens,
                        'top_p' => 1,
                        'stream' => false,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $reply = $data['choices'][0]['message']['content'] ?? null;
                        if (!empty($reply)) {
                            $reply = $this->cleanAiReply($reply);
                            return [
                                'success' => true,
                                'reply' => $reply,
                                'provider' => 'Groq (' . $model . ')',
                            ];
                        }
                    } else {
                        $err = $response->json();
                        $lastError = 'Groq ' . $model . ': ' . ($err['error']['message'] ?? $response->status());
                    }
                } catch (\Throwable $e) {
                    $lastError = 'Groq error: ' . $e->getMessage();
                }
            }
        }

        // 2. Backup: OpenRouter API
        $openrouterKey = env('OPENROUTER_API_KEY');
        if (!empty($openrouterKey)) {
            foreach ($this->openrouterModels as $model) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $openrouterKey,
                        'HTTP-Referer' => 'http://127.0.0.1:8080',
                        'X-Title' => 'Sistem Kas Informatika UPS',
                        'Content-Type' => 'application/json',
                    ])->timeout(20)->post('https://openrouter.ai/api/v1/chat/completions', [
                        'model' => $model,
                        'messages' => $messages,
                        'temperature' => $temperature,
                        'max_tokens' => $maxTokens,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $reply = $data['choices'][0]['message']['content'] ?? null;
                        if (!empty($reply)) {
                            $reply = $this->cleanAiReply($reply);
                            return [
                                'success' => true,
                                'reply' => $reply,
                                'provider' => 'OpenRouter Backup (' . $model . ')',
                            ];
                        }
                    } else {
                        $err = $response->json();
                        $lastError = 'OpenRouter ' . $model . ': ' . ($err['error']['message'] ?? $response->status());
                    }
                } catch (\Throwable $e) {
                    $lastError = 'OpenRouter error: ' . $e->getMessage();
                }
            }
        }

        $isTokenExhausted = (bool) preg_match('/(?:rate.?limit|429|quota|token|credits?|tpm|rpd|too many requests|insufficient_quota|resource_exhausted)/i', $lastError);

        return [
            'success' => false,
            'error' => $lastError ?: 'Semua provider AI tidak merespons',
            'is_token_exhausted' => $isTokenExhausted,
        ];
    }

    private function cleanAiReply(string $text): string
    {
        $text = preg_replace('/<think>.*?<\/think>/s', '', $text);
        return trim($text);
    }

    /**
     * Notifikasi Ramah & Solutif saat Kuota Token AI Sedang Habis / Mencapai Batas Limit
     */
    private function generateFriendlyTokenExhaustedNotice($user, string $userMessage = ''): string
    {
        $activeClassSemester = AcademicService::getActiveSemester();
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
        $tahunAjaran = AcademicService::getTahunAjaran($activeClassSemester);
        $duesRate = AcademicService::getDuesRatePerWeek();

        $totalIn = (float) KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
        $totalOut = (float) KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
        $saldo = $totalIn - $totalOut;

        $bendahara = User::where('role', 'bendahara')->first();
        $bendaharaName = $bendahara ? "{$bendahara->name} (NPM: {$bendahara->npm})" : "Bendahara Kelas";

        $userName = $user ? $user->name : 'Teman-teman';

        // Deteksi jika pesan pengguna menanyakan data dasar, sertakan jawaban instan langsung dari database
        $lowerMsg = strtolower($userMessage);
        $instantAnswer = '';
        if (preg_match('/\b(?:saldo|uang\s*kas|sisa\s*uang)\b/i', $lowerMsg)) {
            $instantAnswer = "\n\n💡 **Jawaban Instan dari Database Kelas**:\n"
                . "• **Saldo Kas Aktif ({$activeClassSemester})**: **Rp " . $this->formatRp($saldo) . "** (Pemasukan: Rp " . $this->formatRp($totalIn) . " | Pengeluaran: Rp " . $this->formatRp($totalOut) . ")";
        } elseif (preg_match('/\b(?:tarif|biaya\s*kas|iuran)\b/i', $lowerMsg)) {
            $instantAnswer = "\n\n💡 **Jawaban Instan dari Database Kelas**:\n"
                . "• **Tarif Iuran Mingguan ({$activeClassSemester})**: **Rp " . $this->formatRp($duesRate) . " / minggu**";
        } elseif (preg_match('/\b(?:bendahara|siapa\s+bendahara|kontak)\b/i', $lowerMsg)) {
            $instantAnswer = "\n\n💡 **Kontak Pengurus Kelas**:\n"
                . "• **Bendahara Kelas**: **{$bendaharaName}**";
        }

        return "☕ **Halo {$userName}! Kuota Token AI Sedang Beristirahat Sejenak**\n\n"
            . "Terima kasih banyak atas antusiasmenya! Saat ini kuota token komputasi AI harian sistem kami sedang mencapai batas pemakaian karena banyaknya pertanyaan yang masuk hari ini."
            . $instantAnswer . "\n\n"
            . "📌 **Jangan Khawatir, Anda Tetap Bisa Mengakses Seluruh Fitur Secara Normal**:\n"
            . "• **Buku Kas & Transaksi**: Buka menu [Buku Kas](" . route('kas.index') . ") pada sidebar navigasi kiri untuk melihat seluruh mutasi.\n"
            . "• **Laporan Arsip**: Buka menu [Arsip & Rekapitulasi](" . route('arsip.index') . ") untuk rangkuman per bulan maupun semester.\n"
            . "• **Unduh Rekap Excel**: Gunakan tombol [Unduh Rekap Kas Excel](" . route('kas.exportExcel') . ") untuk mengunduh pembukuan resmi.\n"
            . "• **Urusan Pembayaran**: Silakan menghubungi Bendahara Kelas (**{$bendaharaName}**).\n\n"
            . "✨ *Layanan AI cerdas ini akan otomatis terisi kembali dalam beberapa saat. Terima kasih atas pengertian dan kerja sama teman-teman Informatika UPS Tegal!* 🙏😊";
    }

    /**
     * Guardrail Keamanan & Etika dengan Sistem Strike Moderasi (Batas 3x Blokir Sementara):
     * - Menolak permintaan kredensial privat/rahasia (password, kata sandi, hash, PIN, token)
     * - Mendeteksi pesan kasar/toxic/makian/vulgar dengan normalisasi leetspeak & pemisah karakter
     * - Menerapkan batas toleransi 3x pelanggaran sebelum pemblokiran sementara (15 menit)
     */
    private function handleModerationGuardrails(string $message, $user): ?array
    {
        $msg = trim((string) $message);
        $lower = strtolower($msg);

        // 1. PRIVASI & KREDENSIAL RAHASIA (Private credentials / passwords / hashes / tokens)
        $isAskingHowToChangeOwnPw = preg_match('/\b(?:cara|bagaimana|gimana|panduan|tata\s*cara|letak|posisi|dimana|di\s*mana)\s+.*(?:ganti|ubah|atur|reset)\s+(?:sandi|password|kata\s*sandi|pw)\b/i', $lower) ||
                                   preg_match('/\b(?:ganti|ubah|atur|reset)\s+(?:sandi|password|kata\s*sandi|pw)\s+(?:sendiri|saya|ku|pribadi|di\s*web|di\s*aplikasi)\b/i', $lower);

        if (!$isAskingHowToChangeOwnPw) {
            $privatePatterns = [
                '/\b(?:password(?:nya)?|kata\s*sandi(?:nya)?|sandi(?:nya)?|hash(?:nya)?|bcrypt|credential|kredensial|secret_key|api_key|auth_token)\b/i',
                '/password_hash/i',
                '/\b(?:hack|meretas|bobol|bypass|brute\s*force|injeksi|exploit)\s+(?:akun|password|sandi|sistem|login|database)\b/i',
            ];

            foreach ($privatePatterns as $pattern) {
                if (preg_match($pattern, $lower)) {
                    return [
                        'success' => true,
                        'is_warning' => false,
                        'is_blocked' => false,
                        'reply' => "🔒 **Akses Ditolak (Privasi & Keamanan)**: Informasi kata sandi (password), hash, PIN, dan kredensial akun bersifat rahasia dan terlindungi. Sistem tidak akan pernah membocorkan atau membagikan kredensial pengguna kepada siapa pun demi menjaga privasi dan keamanan sistem.",
                        'http_status' => 200,
                    ];
                }
            }
        }

        // 2. DETEKSI KONTEN TOXIC & VULGAR DENGAN SISTEM STRIKE (BATAS 3X BLOKIR)
        $detectedWord = $this->detectToxicOrVulgar($lower);

        if ($detectedWord !== null) {
            $strikeKey = 'chatbot_strikes_user_' . $user->id;
            $blockKey = 'chatbot_blocked_user_' . $user->id;

            $strikes = (int) Cache::get($strikeKey, 0) + 1;
            Cache::put($strikeKey, $strikes, 86400); // Masa aktif strike 24 jam

            if ($strikes >= 3) {
                $lockDuration = 900; // Blokir 15 menit (900 detik)
                Cache::put($blockKey, time() + $lockDuration, $lockDuration);
                Cache::forget($strikeKey); // Reset strike setelah sanksi blokir diterapkan

                AuditLog::record(
                    'chatbot_diblokir',
                    "Akses Chatbot diblokir sementara 15 menit untuk {$user->name} ({$user->npm}) karena telah 3x mengirim pesan toxic/vulgar. Pesan: \"{$msg}\"",
                    $user,
                    'chatbot',
                    ['strikes' => 3, 'detected_word' => $detectedWord, 'toxic_message' => $msg, 'lockout_seconds' => $lockDuration]
                );

                return [
                    'success' => false,
                    'is_warning' => false,
                    'is_blocked' => true,
                    'remaining_seconds' => $lockDuration,
                    'reply' => "🚫 **BATAS TOLERANSI TERCAPAI (3/3) - AKSES CHATBOT DIBLOKIR SEMENTARA**\n\n"
                        . "Anda telah melakukan pelanggaran etika berbahasa (mengirim kata toxic atau vulgar) sebanyak **3 kali**.\n\n"
                        . "• **Sanksi Keamanan**: Akses Chatbot Anda telah **DIBLOKIR SEMENTARA selama 15 MENIT**.\n"
                        . "• **Pencatatan**: Insiden pelanggaran ini telah terekam secara resmi pada Jejak Audit Keamanan Sistem.\n\n"
                        . "Layanan Chatbot akan pulih secara otomatis setelah masa pemblokiran berakhir. Harap gunakan bahasa yang beretika, sopan, dan santun di lingkungan akademik.",
                    'http_status' => 403,
                ];
            } else {
                $sisa = 3 - $strikes;

                AuditLog::record(
                    'chatbot_toxic_warning',
                    "Peringatan toxic/vulgar ({$strikes}/3) untuk {$user->name} ({$user->npm}). Kata: '{$detectedWord}'. Pesan: \"{$msg}\"",
                    $user,
                    'chatbot',
                    ['strike' => $strikes, 'detected_word' => $detectedWord, 'toxic_message' => $msg]
                );

                return [
                    'success' => true,
                    'is_warning' => true,
                    'is_blocked' => false,
                    'strikes' => $strikes,
                    'remaining_strikes' => $sisa,
                    'reply' => "⚠️ **PERINGATAN PELANGGARAN ETIKA ({$strikes}/3)**\n\n"
                        . "Pesan Anda terdeteksi mengandung kata-kata yang tidak pantas, kasar, atau vulgar (*{$detectedWord}*).\n\n"
                        . "• **Batas Toleransi**: Maksimal **3 kali** pelanggaran.\n"
                        . "• **Sisa Kesempatan**: **{$sisa} kali lagi** sebelum akses Chatbot Anda **DIBLOKIR SEMENTARA**.\n\n"
                        . "Harap gunakan bahasa yang sopan dan santun demi kenyamanan bersama di lingkungan akademik S1 Informatika UPS Tegal.",
                    'http_status' => 200,
                ];
            }
        }

        return null;
    }

    /**
     * Mesin Pendeteksi Kata Kasar, Toxic, dan Konten Vulgar dengan Normalisasi Lanjutan
     */
    private function detectToxicOrVulgar(string $text): ?string
    {
        $clean = strtolower($text);

        // 1. Cek langsung kata-kata kasar / makian / ujaran kebencian
        $badWords = [
            'anjing', 'anjir', 'anjrit', 'asuu', 'asu', 'babi', 'bangsat', 'bgst',
            'bajingan', 'bangke', 'keparat', 'biadab', 'setan', 'iblis', 'jancok', 'jancuk',
            'dancok', 'cok', 'cuk', 'pantek', 'pante', 'puki', 'pukimak', 'kontol', 'kntl',
            'memek', 'mmk', 'jembut', 'itil', 'pepek', 'peler', 'pler', 'tolol', 'goblok',
            'gblg', 'bego', 'idiot', 'dungu', 'sinting', 'brengsek', 'kampret', 'taik', 'tai',
            'bacot', 'bacod', 'bokep', 'porno', 'pornografi', 'porn', 'nsfw', 'seks', 'sex',
            'ngentot', 'entot', 'ngewe', 'colmek', 'coli', 'ngocok', 'tetek', 'toket', 'payudara',
            'sange', 'lonte', 'perek', 'pelacur', 'open bo', 'vcs', 'fuck', 'fck', 'fucking',
            'bitch', 'bastard', 'asshole', 'dick', 'pussy', 'cunt', 'whore', 'slut'
        ];

        foreach ($badWords as $bw) {
            if (preg_match('/\b' . preg_quote($bw, '/') . '\b/i', $clean)) {
                return $bw;
            }
        }

        // 2. Normalisasi pemisah karakter dan leetspeak (misal: "k.o.n.t.o.l", "k o n t o l", "k0nt0l", "m3m3k", "a n j i n g")
        $noPunctuation = preg_replace('/[\s\._\-\*]+/', '', $clean);
        $leetspeakMap = [
            '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's',
            '7' => 't', '8' => 'b', '@' => 'a', '!' => 'i', '$' => 's',
        ];
        $normalized = strtr($noPunctuation, $leetspeakMap);
        $collapsed = preg_replace('/(.)\1+/', '$1', $normalized);

        $severeWords = [
            'kontol', 'kntl', 'memek', 'mmk', 'ngentot', 'entot', 'jembut', 'pepek',
            'pantek', 'peler', 'anjing', 'bangsat', 'bgst', 'goblok', 'tolol', 'bajingan',
            'bitch', 'asshole', 'bokep', 'sange', 'lonte', 'fuck'
        ];

        foreach ($severeWords as $sw) {
            if (str_contains($normalized, $sw) || str_contains($collapsed, $sw)) {
                return $sw;
            }
        }

        // 3. Frasa toksik spesifik
        $toxicPhrases = [
            'pala bapak', 'mati lu', 'bego lu', 'tolol lu', 'goblok lu', 'tai lu', 'bacot lu', 'dasar anjing', 'dasar babi'
        ];
        foreach ($toxicPhrases as $phrase) {
            if (str_contains($clean, $phrase)) {
                return $phrase;
            }
        }

        return null;
    }

    /**
     * Sistem Deteksi Aksi Dua Tingkat (Tier 1: Regex Fleksibel, Tier 2: AI Action Parser)
     * Langsung mengeksekusi ke database jika diizinkan, atau menolak jika di luar wewenang role.
     */
    private function detectAndExecuteAction($message, $user)
    {
        if (!$user) {
            return null;
        }

        $msg = strtolower(trim($message));
        $userRole = $user->role;
        $userRoleLabel = $this->getRoleLabel($userRole);

        $bendahara = User::where('role', 'bendahara')->first();
        $bendaharaName = $bendahara ? "{$bendahara->name} (NPM: {$bendahara->npm})" : "Bendahara Kelas";

        $ketua = User::where('role', 'ketua')->first();
        $ketuaName = $ketua ? "{$ketua->name} (NPM: {$ketua->npm})" : "Ketua Kelas";

        $admin = User::where('role', 'admin')->first();
        $adminName = $admin ? "{$admin->name} (NPM: {$admin->npm})" : "Super Admin";

        // Cek respon konfirmasi aksi yang tertunda (jika ada aksi menunggu konfirmasi)
        $pending = session('chatbot_pending_action');
        if ($pending && isset($pending['user_id']) && $pending['user_id'] === $user->id) {
            if (time() - ($pending['created_at'] ?? 0) <= 600) {
                // 1. Respon pembatalan
                if (preg_match('/^(?:batal|batalkan|cancel|tidak|nggak|gak|jangan|ga jadi|batalin|no|stop)\b/i', $msg) ||
                    preg_match('/(?:batalkan|batal aksi|jangan lakukan|ga usah)/i', $msg)) {
                    session()->forget('chatbot_pending_action');
                    return "Tindakan telah dibatalkan. Tidak ada perubahan data yang dilakukan.";
                }

                // 2. Respon persetujuan / konfirmasi
                if (preg_match('/^(?:ya|iya|y|yes|ok|oke|lanjut|lanjutkan|konfirmasi|setuju|eksekusi|deal|bisa|teruskan|laksanakan)\b/i', $msg) ||
                    preg_match('/(?:ya,?\s*konfirmasi|konfirmasi aksi|laksanakan|setuju|lanjutkan|eksekusi sekarang)/i', $msg)) {
                    $actionData = $pending['action_data'];
                    session()->forget('chatbot_pending_action');
                    return $this->executeParsedAction($actionData, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $pending['raw_message'] ?? $message);
                }
            } else {
                session()->forget('chatbot_pending_action');
            }
        }

        // Deteksi Permintaan Pembuatan Pesan Broadcast WhatsApp / Pengingat Kas Nunggak
        $isWaBroadcastRequest = false;
        if (preg_match('/\b(?:wa|whatsapp)\b/i', $msg)) {
            if (!preg_match('/\b(?:nomor|no|nohp|kontak|telp|telepon|call)\b/i', $msg)) {
                if (preg_match('/\b(?:broadcast|pengingat|reminder|tagih(?:an)?|nunggak|tunggakan|pesan|teks|format|template|rekap)\b/i', $msg)) {
                    $isWaBroadcastRequest = true;
                }
            }
        } elseif (preg_match('/\b(?:broadcast|pengingat|tagihan)\s*(?:kas|iuran)?\s*(?:untuk\s+)?(?:grup|kelas)\b/i', $msg) ||
                  preg_match('/\b(?:broadcast)\s*(?:kas|iuran|nunggak|tunggakan)\b/i', $msg) ||
                  preg_match('/\b(?:pesan|teks|format)\s+(?:pengingat|tagihan)\s+(?:kas|iuran)\b/i', $msg)) {
            $isWaBroadcastRequest = true;
        }

        if ($isWaBroadcastRequest) {
            return [
                'reply' => $this->generateWaBroadcastNunggak($user),
                'is_confirmation' => false,
            ];
        }

        // Deteksi Permintaan Lihat Backup / Transaksi Kas Terhapus (KHUSUS BENDAHARA SAJA)
        $isListDeletedRequest = (
            preg_match('/(?:transaksi|kas|catatan|data|riwayat)\s*(?:kas\s*)?(?:yang\s+)?(?:sudah\s+|pernah\s+)?(?:dihapus|terhapus)/i', $msg) ||
            preg_match('/(?:daftar|lihat|tampilkan|cek)\s+(?:backup|sampah|trash|riwayat\s+hapus|transaksi\s+(?:ter)?hapus)/i', $msg) ||
            preg_match('/\bbackup\s*(?:kas|transaksi)\b/i', $msg)
        ) && !preg_match('/^(?:tolong\s+|coba\s+)?(?:pulihkan|pulih|restore|kembalikan)\b/i', $msg);

        if ($isListDeletedRequest) {
            if ($user->role !== 'bendahara') {
                return [
                    'reply' => "❌ **Akses Ditolak**: Anda login sebagai **{$userRoleLabel}**. Melihat daftar backup dan memulihkan transaksi kas terhapus **hanya berhak dilakukan oleh Bendahara Kelas** ({$bendaharaName}).",
                    'is_confirmation' => false,
                ];
            }
            return [
                'reply' => $this->listDeletedTransactions($user),
                'is_confirmation' => false,
            ];
        }

        // Deteksi permintaan mutasi arsip langsung (read-only guard)
        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:hapus|delete|hilangkan|edit|ubah|ganti|tambah|input|buat)\s+(?:data\s+)?(?:arsip|rekap(?:an)?|rekapitulasi)\b/i', $msg) ||
            preg_match('/^(?:tolong\s+|coba\s+)?(?:arsip|rekap(?:an)?|rekapitulasi)\s+(?:di\s*)?(?:hapus|delete|diedit|diubah|ditambah|diinput)\b/i', $msg)) {
            return "Data pada Menu **Arsip & Rekapitulasi** tidak dapat ditambah, diedit, atau dihapus secara langsung demi menjaga transparansi, integritas, dan kejujuran pembukuan kelas.\n\n"
                . "Halaman Arsip adalah dokumen audit murni (read-only) yang terhubung secara otomatis dengan database transaksi riil:\n"
                . "• **Tersinkronisasi Otomatis**: Jika Bendahara Kelas mengubah atau menghapus data transaksi kas sumber dengan penginputan data yang jelas dan konfirmasi sah di Buku Kas, maka data arsip akan otomatis berubah atau terhapus menyesuaikan secara real-time.\n"
                . "• **Fungsi AI**: Saya (AI) dapat melihat, menganalisis, dan merangkum seluruh data arsip kas (per bulan, per semester, per tahun, maupun sepanjang waktu) untuk menjawab pertanyaan Anda!";
        }

        // Teruskan langsung ke AI jika input murni pertanyaan/informasi
        $isPureQuestion = preg_match('/^(?:siapa|berapa|kapan|kenapa|mengapa|apakah|adakah|gimana|bagaimana|tolong\s+jelaskan|jelaskan|apa\s+itu|tampilkan|lihat|ada\s+apa|apa\s+saja|fitur|menu|panduan|info|informasi|cara)\b/i', $msg) ||
                          (str_ends_with(rtrim($msg), '?') && !preg_match('/^(?:catat|input|tambah|hapus|edit|ubah|batalkan|atur|daftarkan)\b/i', $msg));

        if ($isPureQuestion) {
            return null; // Teruskan ke AI Q&A normal
        }

        // Filter intensi aksi mutasi kas atau akun
        $actionTriggerWords = '/\b(?:catat|tambah|tambahkan|tambahin|input|masukkan|masukin|keluar|pengeluaran|pemasukan|kurang|edit|ubah|ganti|perbarui|update|hapus|delete|hilangkan|jadikan|tetapkan|daftarkan|buat|set|beli|biaya|bayar|batal|batalkan|batalin|cancel|pulihkan|pulih|restore|kembalikan)\b/i';
        if (!preg_match($actionTriggerWords, $msg)) {
            return null; // Bukan perintah mutasi, teruskan ke Q&A normal
        }

        // Heuristik cepat pencocokan aksi (Tier 1 fast matcher)

        // A. Deteksi Pembatalan / Penghapusan Iuran Mahasiswa (contoh: "hapus catatan iuran tegar", "batalkan iuran tegar minggu 2")
        if (preg_match('/(?:hapus|batalkan|batal|hilangkan|cancel)\s+(?:catat\s*an\s+)?(?:iuran|kas|uang\s+kas|pembayaran\s+kas|pembayaran)\s*(?:untuk|atas\s+nama|si\s+)?\s*(.+)/i', $msg, $mDues) ||
            preg_match('/(?:hapus|batalkan|batal|hilangkan|cancel)\s+(.+?)\s+(?:catat\s*an\s+)?(?:iuran|kas|uang\s+kas|pembayaran)/i', $msg, $mDues)) {
            
            $rawTarget = trim($mDues[1]);

            // Ekstrak minggu jika dicantumkan
            $week = null;
            if (preg_match('/(?:minggu|mg)(?:\s*ke|\s*[-_])?\s*(\d+)/i', $msg, $mWk) ||
                preg_match('/(?:minggu|mg)\s*(\d+)/i', $msg, $mWk)) {
                $week = (int) $mWk[1];
            }

            // Bersihkan nama mahasiswa dari kata-kata pengiring
            $cleanedName = preg_replace('/(?:minggu|mg)(?:\s*ke|\s*[-_])?\s*\d+/i', '', $rawTarget);
            $cleanedName = preg_replace('/\b(?:minggu|mg|ke|bulan|bln|semester|catatan|kas|iuran|uang|pembayaran|dong|tolong|coba|plis|please)\b/i', '', $cleanedName);
            $cleanedName = trim($cleanedName);

            $targetStudent = null;
            if (!empty($cleanedName)) {
                $targetStudent = User::where('name', 'like', "%{$cleanedName}%")
                    ->orWhere('npm', $cleanedName)
                    ->first();

                if (!$targetStudent) {
                    $tokens = preg_split('/\s+/', $cleanedName);
                    foreach ($tokens as $tok) {
                        if (strlen($tok) >= 3) {
                            $found = User::where('name', 'like', "%{$tok}%")->first();
                            if ($found) {
                                $targetStudent = $found;
                                break;
                            }
                        }
                    }
                }
            }

            if ($targetStudent) {
                $parsedAction = [
                    'action' => 'batalkan_iuran',
                    'student' => $targetStudent->name,
                    'week' => $week,
                ];
                return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
            }
        }

        // B. Deteksi Ubah Semester (contoh: "ubah semester aktif ke Semester 6", "ganti semester 4", "ubah semester")
        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:ubah|ganti|pindah|set)\s+(?:semester|smt)(?:\s+aktif)?(?:\s+ke|\s+jadi)?\s*(.*)/i', $msg, $mSem)) {
            $semQuery = trim($mSem[1] ?? '');
            $parsedAction = [
                'action' => 'ubah_semester',
                'semester' => $semQuery,
            ];
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        // D. Deteksi Perintah Hapus Transaksi Kas Tanpa ID (contoh: "hapus transaksi", "hapus kas", "hapus catatan kas")
        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:hapus|delete|hilangkan)\s+(?:data\s+)?(?:transaksi|kas|catatan kas|mutasi)(?:\s+kas)?$/i', $msg) ||
            preg_match('/^(?:tolong\s+|coba\s+)?(?:hapus|delete|hilangkan)\s+transaksi\s*$/i', $msg)) {
            $parsedAction = [
                'action' => 'hapus_transaksi',
                'id' => null,
            ];
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        // E. Deteksi Perintah Edit Transaksi Kas Tanpa ID (contoh: "edit transaksi", "ubah transaksi")
        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:edit|ubah|ganti|perbarui)\s+(?:data\s+)?(?:transaksi|kas|catatan kas)(?:\s+kas)?$/i', $msg)) {
            $parsedAction = [
                'action' => 'edit_transaksi',
                'id' => null,
            ];
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        // F. Deteksi Perintah Mutasi Kas Tanpa Atribut Lengkap
        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:catat|tambah|input|masukkan)\s+(?:data\s+)?pemasukan(?:\s+kas)?$/i', $msg)) {
            $parsedAction = [
                'action' => 'catat_pemasukan',
                'amount' => 0,
                'description' => null,
            ];
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:catat|tambah|input|masukkan)\s+(?:data\s+)?pengeluaran(?:\s+kas)?$/i', $msg)) {
            $parsedAction = [
                'action' => 'catat_pengeluaran',
                'amount' => 0,
                'description' => null,
            ];
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:atur|ubah|ganti|set)\s+(?:tarif|tarif kas|tarif iuran|iuran mingguan)$/i', $msg)) {
            $parsedAction = [
                'action' => 'atur_tarif_iuran',
                'amount' => 0,
            ];
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        // G. Deteksi Tambah Akun Tanpa Atribut Lengkap
        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:tambah|daftarkan|buat)\s+(?:data\s+)?(?:akun|pengguna|user)$/i', $msg)) {
            $parsedAction = [
                'action' => 'tambah_akun',
                'name' => null,
                'npm' => null,
            ];
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        // H. Deteksi Pulihkan / Restore Transaksi Kas Terhapus (contoh: "pulihkan transaksi #12", "restore kas 12", "pulihkan transaksi")
        if (preg_match('/^(?:tolong\s+|coba\s+)?(?:pulihkan|pulih|restore|kembalikan)\s+(?:data\s+)?(?:transaksi|kas|catatan\s+kas|mutasi|backup)(?:\s+kas)?(?:\s+(?:id\s*)?#?(\d+))?/i', $msg, $mRestore)) {
            $restoreId = !empty($mRestore[1]) ? (int) $mRestore[1] : null;
            $parsedAction = [
                'action' => 'pulihkan_transaksi',
                'id' => $restoreId,
            ];
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        // Penalaran kontekstual berbasis model AI
        $parsedAction = $this->reasonActionWithAi($message);
        if (!empty($parsedAction['action']) && $parsedAction['action'] !== 'none') {
            return $this->handleActionWithConfirmation($parsedAction, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, $message);
        }

        return null;
    }

    /**
     * Memproses aksi mutasi dengan verifikasi hak akses dan meminta konfirmasi pengguna
     */
    private function handleActionWithConfirmation(array $actionData, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, string $rawMessage)
    {
        if (isset($actionData['parameters']) && is_array($actionData['parameters'])) {
            foreach ($actionData['parameters'] as $k => $v) {
                if (!isset($actionData[$k])) {
                    $actionData[$k] = $v;
                }
            }
        }

        $action = $actionData['action'] ?? 'none';
        $userRole = $user->role;

        // 1. Cek otorisasi hak akses terlebih dahulu
        switch ($action) {
            case 'atur_tarif_iuran':
            case 'set_tarif_iuran':
            case 'catat_pemasukan':
            case 'catat_pengeluaran':
            case 'hapus_transaksi':
            case 'edit_transaksi':
            case 'batalkan_iuran':
            case 'hapus_iuran':
            case 'pulihkan_transaksi':
                if ($userRole !== 'bendahara') {
                    return "❌ Akses Ditolak: Anda login sebagai **{$userRoleLabel}**. Pengelolaan kas dan pemulihan backup transaksi **hanya berhak dilakukan oleh Bendahara Kelas** ({$bendaharaName}).";
                }
                break;

            case 'ubah_role':
            case 'tambah_akun':
            case 'hapus_akun':
            case 'ubah_semester':
                if (!in_array($userRole, ['admin', 'ketua'])) {
                    return "❌ Akses Ditolak: Anda login sebagai **{$userRoleLabel}**. Pengelolaan akun dan semester kelas hanya berhak dilakukan oleh Super Admin atau Ketua Kelas.";
                }
                break;

            default:
                return null;
        }

        // 2. Siapkan data konfirmasi spesifik berdasarkan tipe aksi
        $confirmationMsg = '';
        switch ($action) {
            case 'atur_tarif_iuran':
            case 'set_tarif_iuran':
                $amount = (int) ($actionData['amount'] ?? 0);
                $currentRate = AcademicService::getDuesRatePerWeek();
                if ($amount < 500) {
                    return "Untuk mengatur tarif iuran mingguan kas, mohon cantumkan atribut **Nominal Tarif Baru** secara detail:\n\n"
                        . "• **Nominal Tarif Baru**: Besaran iuran per mahasiswa per minggu minimal Rp 500 (contoh: *5000* atau *10rb*)\n"
                        . "• **Tarif Saat Ini**: Rp " . number_format($currentRate, 0, ',', '.') . " / minggu\n\n"
                        . "Contoh perintah: *ubah tarif kas jadi 5000*.";
                }
                $confirmationMsg = "⚠️ **Konfirmasi Pengaturan Tarif Iuran Kas**\n\n"
                    . "Mohon periksa dan konfirmasi perubahan tarif iuran mingguan berikut:\n"
                    . "• **Tarif Saat Ini**: Rp " . number_format($currentRate, 0, ',', '.') . " / minggu\n"
                    . "• **Tarif Baru**: Rp " . number_format($amount, 0, ',', '.') . " / minggu\n"
                    . "• **Otoritas**: Bendahara ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk menerapkan tarif ini, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'catat_pemasukan':
                $amount = (float) ($actionData['amount'] ?? 0);
                if ($amount < 500) {
                    return "Untuk mencatat pemasukan kas, mohon lengkapi atribut berikut secara detail:\n\n"
                        . "• **Nominal**: Besaran uang masuk minimal Rp 500 (contoh: *20000*, *50rb*)\n"
                        . "• **Keterangan**: Sumber pemasukan kas atau keperluannya\n"
                        . "• **Nama Mahasiswa**: (Khusus jika pembayaran iuran kas mingguan anggota)\n\n"
                        . "Contoh perintah: *catat pemasukan 50000 sumbangan kas kelas* atau *catat pemasukan iuran Gilang 20000*.";
                }
                $desc = trim((string) ($actionData['description'] ?? 'Pemasukan kas'));
                $student = isset($actionData['student']) ? (string) $actionData['student'] : null;
                $targetStudent = $this->resolveStudentForDues($student, $desc, $rawMessage, $user);
                $targetLabel = $targetStudent ? "{$targetStudent->name} (NPM: {$targetStudent->npm})" : "Kas Kelas Umum";

                $confirmationMsg = "⚠️ **Konfirmasi Pencatatan Pemasukan Kas**\n\n"
                    . "Mohon periksa dan konfirmasi rincian pemasukan kas berikut:\n"
                    . "• **Tipe Transaksi**: Pemasukan Kas (Debit)\n"
                    . "• **Nominal**: Rp " . number_format($amount, 0, ',', '.') . "\n"
                    . "• **Keterangan**: {$desc}\n"
                    . "• **Pihak / Mahasiswa**: {$targetLabel}\n"
                    . "• **Otoritas**: Bendahara ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk mencatat transaksi ini, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'catat_pengeluaran':
                $amount = (float) ($actionData['amount'] ?? 0);
                if ($amount < 500) {
                    return "Untuk mencatat pengeluaran kas, mohon lengkapi atribut berikut secara detail:\n\n"
                        . "• **Nominal**: Besaran uang keluar minimal Rp 500 (contoh: *25000*, *100rb*)\n"
                        . "• **Keterangan**: Keperluan belanja atau pengeluaran operasional kelas\n\n"
                        . "Contoh perintah: *catat pengeluaran 25000 beli spidol boardmarker*.";
                }
                $activeClassSemester = AcademicService::getActiveSemester();
                $currentIn = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
                $currentOut = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
                $currentBalance = $currentIn - $currentOut;

                if ($amount > $currentBalance) {
                    return "Pengeluaran Rp " . number_format($amount, 0, ',', '.') . " melebihi sisa saldo kas aktif (Rp " . number_format($currentBalance, 0, ',', '.') . ").\n\nSaldo kas saat ini tidak mencukupi untuk melakukan transaksi ini.";
                }

                $desc = trim((string) ($actionData['description'] ?? 'Pengeluaran kas'));
                if (empty($desc) || strtolower($desc) === 'pengeluaran kas') {
                    $desc = 'Belanja keperluan operasional kelas';
                }

                $confirmationMsg = "⚠️ **Konfirmasi Pencatatan Pengeluaran Kas**\n\n"
                    . "Mohon periksa dan konfirmasi rincian pengeluaran kas berikut:\n"
                    . "• **Tipe Transaksi**: Pengeluaran Kas (Kredit)\n"
                    . "• **Nominal**: Rp " . number_format($amount, 0, ',', '.') . "\n"
                    . "• **Keterangan**: {$desc}\n"
                    . "• **Sisa Saldo Setelah Ini**: Rp " . number_format($currentBalance - $amount, 0, ',', '.') . "\n"
                    . "• **Otoritas**: Bendahara ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk mencatat transaksi ini, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'batalkan_iuran':
            case 'hapus_iuran':
                $studentParam = trim((string) ($actionData['student'] ?? ''));
                $targetStudent = null;

                if (!empty($studentParam)) {
                    $targetStudent = User::where('name', 'like', "%{$studentParam}%")
                        ->orWhere('npm', $studentParam)
                        ->first();
                }

                if (!$targetStudent) {
                    $targetStudent = $this->resolveStudentForDues($studentParam, '', $rawMessage, null);
                }

                if (!$targetStudent) {
                    return "Mohon sebutkan nama atau NPM mahasiswa yang catatan iurannya ingin dibatalkan/dihapus.\n\nContoh: *hapus catatan iuran Tegar* atau *batalkan iuran Tegar minggu ke-1*.";
                }

                $activeClassSemester = AcademicService::getActiveSemester();

                // Ambil daftar iuran lunas mahasiswa di semester aktif
                $paidDues = DuesPayment::where('user_id', $targetStudent->id)
                    ->where('semester', $activeClassSemester)
                    ->where('status', 'lunas')
                    ->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->orderBy('week_number', 'asc')
                    ->get();

                if ($paidDues->isEmpty()) {
                    return "Mahasiswa **{$targetStudent->name}** (NPM: {$targetStudent->npm}) saat ini belum memiliki catatan pembayaran iuran yang berstatus lunas pada {$activeClassSemester}.\n\nTidak ada catatan iuran yang perlu dibatalkan.";
                }

                $week = isset($actionData['week']) && is_numeric($actionData['week']) ? (int) $actionData['week'] : null;

                // JIKA BENDAHARA BELUM MENCANTUMKAN MINGGU BERAPA:
                // Jangan langsung dieksekusi! Tanyakan secara terperinci minggu berapa.
                if ($week === null || $week < 1 || $week > 4) {
                    $duesList = [];
                    foreach ($paidDues as $pd) {
                        $duesList[] = "• **Minggu ke-{$pd->week_number}** (Bulan {$pd->month}/{$pd->year}) - Status: Lunas";
                    }

                    return "Mahasiswa **{$targetStudent->name}** (NPM: {$targetStudent->npm}) tercatat memiliki pembayaran iuran lunas pada {$activeClassSemester} sebagai berikut:\n"
                        . implode("\n", $duesList) . "\n\n"
                        . "Catatan iuran **Minggu ke berapa** yang ingin Anda batalkan/hapus?\n"
                        . "Silakan sebutkan minggunya secara terperinci, contoh: *batalkan iuran {$targetStudent->name} minggu ke-" . $paidDues->first()->week_number . "*.";
                }

                // JIKA BENDAHARA SUDAH MENCANTUMKAN MINGGU:
                $targetDue = $paidDues->where('week_number', $week)->first();
                if (!$targetDue) {
                    $paidWeeksStr = $paidDues->pluck('week_number')->map(function ($w) {
                        return "Minggu ke-{$w}";
                    })->implode(', ');

                    return "Catatan iuran **Minggu ke-{$week}** untuk mahasiswa **{$targetStudent->name}** tidak ditemukan atau belum berstatus lunas pada {$activeClassSemester}.\n\n"
                        . "Catatan iuran yang lunas saat ini: {$paidWeeksStr}.\n"
                        . "Silakan sebutkan minggu yang ingin dibatalkan, contoh: *batalkan iuran {$targetStudent->name} minggu ke-" . $paidDues->first()->week_number . "*.";
                }

                $rate = AcademicService::getDuesRatePerWeek();
                $actionData['student_id'] = $targetStudent->id;
                $actionData['student_name'] = $targetStudent->name;
                $actionData['student_npm'] = $targetStudent->npm;
                $actionData['due_id'] = $targetDue->id;
                $actionData['week'] = $targetDue->week_number;
                $actionData['month'] = $targetDue->month;
                $actionData['year'] = $targetDue->year;
                $actionData['kas_transaction_id'] = $targetDue->kas_transaction_id;

                $confirmationMsg = "⚠️ **Konfirmasi Pembatalan Catatan Iuran Mahasiswa**\n\n"
                    . "Apakah Anda yakin ingin membatalkan catatan iuran kas berikut?\n"
                    . "• **Mahasiswa**: {$targetStudent->name} (NPM: {$targetStudent->npm})\n"
                    . "• **Iuran**: Minggu ke-{$targetDue->week_number} (Bulan {$targetDue->month}/{$targetDue->year})\n"
                    . "• **Semester**: {$targetDue->semester}\n"
                    . "• **Pengurangan Saldo Kas**: Rp " . number_format($rate, 0, ',', '.') . "\n"
                    . "• **Otoritas**: Bendahara ({$user->name})\n\n"
                    . "Catatan: Pembatalan ini akan mengembalikan status minggu tersebut menjadi belum lunas dan menyesuaikan mutasi kas terkait.\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk membatalkan catatan iuran ini, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'hapus_transaksi':
                $id = (int) ($actionData['id'] ?? 0);
                $activeClassSemester = AcademicService::getActiveSemester();

                if ($id <= 0) {
                    $recentKas = KasTransaction::where('semester', $activeClassSemester)
                        ->orderBy('id', 'desc')
                        ->take(5)
                        ->get();

                    if ($recentKas->isEmpty()) {
                        return "Saat ini belum ada data transaksi kas yang tercatat pada {$activeClassSemester}.";
                    }

                    $list = [];
                    foreach ($recentKas as $k) {
                        $dateStr = $k->date ? $k->date->format('d/m/Y') : '-';
                        $list[] = "• **ID #{$k->id}**: " . ucfirst($k->type) . " Rp " . number_format($k->amount, 0, ',', '.') . " - {$k->description} ({$dateStr})";
                    }

                    return "Anda belum mencantumkan nomor ID transaksi kas yang ingin dihapus.\n\n"
                        . "Berikut adalah 5 transaksi kas terbaru pada {$activeClassSemester}:\n"
                        . implode("\n", $list) . "\n\n"
                        . "Silakan sebutkan nomor ID transaksi kas yang ingin Anda hapus.\n"
                        . "Contoh: *hapus transaksi #{$recentKas->first()->id}*.";
                }

                $tx = KasTransaction::find($id);
                if (!$tx) {
                    $recentKas = KasTransaction::where('semester', $activeClassSemester)
                        ->orderBy('id', 'desc')
                        ->take(5)
                        ->get();

                    $list = [];
                    foreach ($recentKas as $k) {
                        $dateStr = $k->date ? $k->date->format('d/m/Y') : '-';
                        $list[] = "• **ID #{$k->id}**: " . ucfirst($k->type) . " Rp " . number_format($k->amount, 0, ',', '.') . " - {$k->description} ({$dateStr})";
                    }

                    return "Transaksi kas dengan ID #{$id} tidak ditemukan di sistem.\n\n"
                        . "Berikut adalah transaksi kas yang tersedia pada {$activeClassSemester}:\n"
                        . implode("\n", $list) . "\n\n"
                        . "Silakan periksa kembali ID transaksi yang ingin Anda hapus, contoh: *hapus transaksi #{$recentKas->first()->id}*.";
                }

                $confirmationMsg = "⚠️ **Konfirmasi Penghapusan Transaksi Kas**\n\n"
                    . "Apakah Anda yakin ingin menghapus data mutasi kas berikut secara permanen?\n"
                    . "• **ID Transaksi**: #{$tx->id}\n"
                    . "• **Tipe**: " . ucfirst($tx->type) . "\n"
                    . "• **Nominal**: Rp " . number_format($tx->amount, 0, ',', '.') . "\n"
                    . "• **Keterangan**: {$tx->description}\n"
                    . "• **Tanggal**: " . ($tx->date ? $tx->date->format('d/m/Y') : '-') . "\n"
                    . "• **Otoritas**: Bendahara ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk menghapus data ini, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'edit_transaksi':
                $id = (int) ($actionData['id'] ?? 0);
                $activeClassSemester = AcademicService::getActiveSemester();

                if ($id <= 0) {
                    $recentKas = KasTransaction::where('semester', $activeClassSemester)
                        ->orderBy('id', 'desc')
                        ->take(5)
                        ->get();

                    if ($recentKas->isEmpty()) {
                        return "Saat ini belum ada data transaksi kas yang tercatat pada {$activeClassSemester}.";
                    }

                    $list = [];
                    foreach ($recentKas as $k) {
                        $dateStr = $k->date ? $k->date->format('d/m/Y') : '-';
                        $list[] = "• **ID #{$k->id}**: " . ucfirst($k->type) . " Rp " . number_format($k->amount, 0, ',', '.') . " - {$k->description} ({$dateStr})";
                    }

                    return "Anda belum mencantumkan nomor ID transaksi kas yang ingin diedit/diperbarui.\n\n"
                        . "Berikut adalah 5 transaksi kas terbaru pada {$activeClassSemester}:\n"
                        . implode("\n", $list) . "\n\n"
                        . "Silakan sebutkan nomor ID transaksi dan data yang ingin diubah.\n"
                        . "Contoh: *edit transaksi #{$recentKas->first()->id} nominal 25000 keterangan Operasional Kelas*.";
                }

                $tx = KasTransaction::find($id);
                if (!$tx) {
                    $recentKas = KasTransaction::where('semester', $activeClassSemester)
                        ->orderBy('id', 'desc')
                        ->take(5)
                        ->get();

                    $list = [];
                    foreach ($recentKas as $k) {
                        $dateStr = $k->date ? $k->date->format('d/m/Y') : '-';
                        $list[] = "• **ID #{$k->id}**: " . ucfirst($k->type) . " Rp " . number_format($k->amount, 0, ',', '.') . " - {$k->description} ({$dateStr})";
                    }

                    return "Transaksi kas dengan ID #{$id} tidak ditemukan di sistem.\n\n"
                        . "Berikut adalah transaksi kas yang tersedia pada {$activeClassSemester}:\n"
                        . implode("\n", $list) . "\n\n"
                        . "Silakan periksa kembali ID transaksi yang ingin Anda ubah, contoh: *edit transaksi #{$recentKas->first()->id} nominal 25000*.";
                }

                $changes = [];
                if (isset($actionData['amount']) && (float) $actionData['amount'] > 0) {
                    $newAmount = (float) $actionData['amount'];
                    $changes[] = "Nominal: Rp " . number_format($tx->amount, 0, ',', '.') . " ➔ **Rp " . number_format($newAmount, 0, ',', '.') . "**";
                }
                if (isset($actionData['description']) && !empty(trim($actionData['description']))) {
                    $newDesc = trim($actionData['description']);
                    $changes[] = "Keterangan: \"{$tx->description}\" ➔ **\"{$newDesc}\"**";
                }
                if (empty($changes)) {
                    return "Mohon sebutkan perubahan nominal atau keterangan yang ingin diperbarui pada transaksi #{$id}.\nContoh: *edit transaksi #{$id} jadi Rp 30000 keterangan Keperluan Kelas*.";
                }

                $confirmationMsg = "⚠️ **Konfirmasi Perubahan Transaksi Kas**\n\n"
                    . "Apakah Anda yakin ingin memperbarui transaksi kas #{$tx->id}?\n"
                    . "• **Data Lama**: Rp " . number_format($tx->amount, 0, ',', '.') . " ({$tx->description})\n"
                    . "• **Perubahan**: " . implode(' | ', $changes) . "\n"
                    . "• **Otoritas**: Bendahara ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk memperbarui transaksi, atau ketik **Batal** untuk membatalkan.";
                break;













            case 'tambah_akun':
                $name = trim((string) ($actionData['name'] ?? ''));
                $npm = trim((string) ($actionData['npm'] ?? ''));
                if (empty($name) || empty($npm)) {
                    return "Untuk mendaftarkan akun pengguna baru ke sistem, mohon cantumkan atribut berikut secara detail:\n\n"
                        . "• **Nama Lengkap**: Nama lengkap mahasiswa atau pengurus (contoh: *Gilang Maulana*)\n"
                        . "• **NPM**: Nomor Pokok Mahasiswa unik (contoh: *24200099*)\n"
                        . "• **Peran (Role)**: Hak akses akun (Pilihan: *mahasiswa*, *bendahara*, *ketua*, atau *admin*)\n\n"
                        . "Contoh perintah: *tambah akun Gilang Maulana npm 24200099 role mahasiswa*.";
                }
                if (User::where('npm', $npm)->exists()) {
                    return "❌ Gagal: Akun dengan NPM {$npm} sudah terdaftar di sistem.";
                }
                $role = strtolower(trim((string) ($actionData['role'] ?? 'mahasiswa')));

                $confirmationMsg = "⚠️ **Konfirmasi Pendaftaran Akun Pengguna**\n\n"
                    . "Apakah Anda yakin ingin mendaftarkan akun baru berikut?\n"
                    . "• **Nama**: {$name}\n"
                    . "• **NPM**: {$npm}\n"
                    . "• **Peran (Role)**: " . $this->getRoleLabel($role) . "\n"
                    . "• **Otoritas**: {$userRoleLabel} ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk membuat akun, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'ubah_role':
                $targetQuery = trim((string) ($actionData['target_user'] ?? ''));
                if (empty($targetQuery)) {
                    $allUsers = User::take(6)->get();
                    $uList = [];
                    foreach ($allUsers as $u) {
                        $uList[] = "• **{$u->name}** (NPM: {$u->npm}, Role: " . $this->getRoleLabel($u->role) . ")";
                    }
                    return "Mohon sebutkan nama atau NPM akun pengguna yang ingin diubah rolenya.\n\n"
                        . "Contoh pengguna kelas:\n"
                        . implode("\n", $uList) . "\n\n"
                        . "Contoh perintah: *ubah role {$allUsers->first()->name} jadi bendahara*.";
                }
                $target = User::where('npm', $targetQuery)->orWhere('name', 'like', "%{$targetQuery}%")->first();
                if (!$target) {
                    return "❌ Gagal: Akun pengguna '{$targetQuery}' tidak ditemukan.";
                }

                $roleRaw = strtolower(trim((string) ($actionData['new_role'] ?? $actionData['role'] ?? 'mahasiswa')));
                $roleMap = ['admin' => 'admin', 'super admin' => 'admin', 'bendahara' => 'bendahara', 'ketua' => 'ketua', 'mahasiswa' => 'mahasiswa'];
                $newRole = $roleMap[$roleRaw] ?? 'mahasiswa';

                $confirmationMsg = "⚠️ **Konfirmasi Perubahan Hak Akses Role**\n\n"
                    . "Apakah Anda yakin ingin mengubah hak akses akun pengguna berikut?\n"
                    . "• **Pengguna**: {$target->name} (NPM: {$target->npm})\n"
                    . "• **Role Lama**: " . $this->getRoleLabel($target->role) . "\n"
                    . "• **Role Baru**: **" . $this->getRoleLabel($newRole) . "**\n"
                    . "• **Otoritas**: {$userRoleLabel} ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk mengubah hak akses, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'hapus_akun':
                $targetQuery = trim((string) ($actionData['target_user'] ?? ''));
                if (empty($targetQuery)) {
                    $allUsers = User::where('id', '!=', $user->id)->take(6)->get();
                    $uList = [];
                    foreach ($allUsers as $u) {
                        $uList[] = "• **{$u->name}** (NPM: {$u->npm}, Role: " . $this->getRoleLabel($u->role) . ")";
                    }
                    return "Mohon sebutkan nama atau NPM akun pengguna yang ingin dihapus.\n\n"
                        . "Contoh pengguna kelas:\n"
                        . implode("\n", $uList) . "\n\n"
                        . "Contoh perintah: *hapus akun {$allUsers->first()->name}*.";
                }
                $target = User::where('npm', $targetQuery)->orWhere('id', $targetQuery)->orWhere('name', 'like', "%{$targetQuery}%")->first();
                if (!$target) {
                    return "❌ Gagal: Akun pengguna '{$targetQuery}' tidak ditemukan.";
                }
                if ($target->id === $user->id) {
                    return "❌ Gagal: Anda tidak dapat menghapus akun Anda sendiri.";
                }

                $confirmationMsg = "⚠️ **Konfirmasi Penghapusan Akun Pengguna**\n\n"
                    . "Apakah Anda yakin ingin menghapus akun pengguna berikut secara permanen?\n"
                    . "• **Nama**: {$target->name}\n"
                    . "• **NPM**: {$target->npm}\n"
                    . "• **Peran**: " . $this->getRoleLabel($target->role) . "\n"
                    . "• **Otoritas**: {$userRoleLabel} ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk menghapus akun ini, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'ubah_semester':
                $targetSemesterRaw = trim((string) ($actionData['semester'] ?? ''));
                $currentSem = AcademicService::getActiveSemester();

                if (empty($targetSemesterRaw)) {
                    $semOptions = [];
                    foreach (AcademicService::SEMESTERS as $s) {
                        $semOptions[] = "• **{$s}** (" . AcademicService::getKelasLabel($s) . ")";
                    }
                    return "Untuk mengubah semester aktif perkuliahan, mohon cantumkan atribut **Semester Baru** secara spesifik:\n\n"
                        . "• **Semester Aktif Saat Ini**: {$currentSem} (" . AcademicService::getKelasLabel($currentSem) . ")\n"
                        . "• **Pilihan Semester Tersedia**:\n"
                        . implode("\n", $semOptions) . "\n\n"
                        . "Contoh perintah: *ubah semester aktif ke Semester 6* atau *ganti semester 4*.";
                }

                if (preg_match('/(\d+)/', $targetSemesterRaw, $m)) {
                    $targetSemester = "Semester " . $m[1];
                } else {
                    $targetSemester = ucwords(strtolower($targetSemesterRaw));
                }

                if (!in_array($targetSemester, AcademicService::SEMESTERS)) {
                    $semOptions = [];
                    foreach (AcademicService::SEMESTERS as $s) {
                        $semOptions[] = "• **{$s}** (" . AcademicService::getKelasLabel($s) . ")";
                    }
                    return "Pilihan semester '{$targetSemesterRaw}' tidak valid.\n\n"
                        . "Pilihan semester yang tersedia di sistem:\n"
                        . implode("\n", $semOptions) . "\n\n"
                        . "Contoh perintah: *ubah semester aktif ke Semester 6*.";
                }

                if ($targetSemester === $currentSem) {
                    return "ℹ️ Sistem saat ini sudah berada pada **{$currentSem}** (" . AcademicService::getKelasLabel($currentSem) . "). Tidak ada perubahan yang diperlukan.";
                }

                $confirmationMsg = "⚠️ **Konfirmasi Pengalihan Semester Aktif**\n\n"
                    . "Apakah Anda yakin ingin mengubah semester perkuliahan aktif kelas?\n"
                    . "• **Semester Saat Ini**: {$currentSem}\n"
                    . "• **Semester Baru**: **{$targetSemester}** (Kelas " . AcademicService::getKelasLabel($targetSemester) . ")\n"
                    . "• **Otoritas**: {$userRoleLabel} ({$user->name})\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk menerapkan, atau ketik **Batal** untuk membatalkan.";
                break;

            case 'pulihkan_transaksi':
                $id = isset($actionData['id']) ? (int) $actionData['id'] : 0;
                $trashedTx = $id > 0 ? KasTransaction::onlyTrashed()->find($id) : null;

                if (!$trashedTx) {
                    $headerMsg = $id > 0
                        ? "⚠️ Transaksi terhapus dengan ID **#{$id}** tidak ditemukan di daftar backup.\n\n"
                        : "Untuk memulihkan transaksi kas yang sudah dihapus, mohon cantumkan **Nomor ID Transaksi**.\n\n";
                    return $headerMsg . $this->listDeletedTransactions($user);
                }

                $relatedTrashedDues = DuesPayment::onlyTrashed()->where('kas_transaction_id', $trashedTx->id)->get();
                $duesNote = $relatedTrashedDues->isNotEmpty()
                    ? "\n• **Iuran Terkait**: {$relatedTrashedDues->count()} catatan iuran mingguan mahasiswa akan ikut dipulihkan."
                    : "";
                $deletedAtStr = $trashedTx->deleted_at ? $trashedTx->deleted_at->format('d/m/Y H:i') . ' WIB' : '-';
                $txDateStr = $trashedTx->date ? $trashedTx->date->format('d/m/Y') : '-';

                $actionData['id'] = $trashedTx->id;
                $confirmationMsg = "♻️ **Konfirmasi Pemulihan Transaksi Kas (Backup)**\n\n"
                    . "Apakah Anda yakin ingin memulihkan transaksi berikut kembali ke pembukuan aktif?\n"
                    . "• **ID Transaksi**: #{$trashedTx->id}\n"
                    . "• **Jenis**: " . ucfirst($trashedTx->type) . "\n"
                    . "• **Nominal**: **Rp " . number_format($trashedTx->amount, 0, ',', '.') . "**\n"
                    . "• **Tanggal Transaksi**: {$txDateStr}\n"
                    . "• **Keterangan**: {$trashedTx->description}\n"
                    . "• **Semester**: {$trashedTx->semester}\n"
                    . "• **Dihapus Pada**: {$deletedAtStr}{$duesNote}\n\n"
                    . "Ketik **Ya** atau **Konfirmasi** untuk memulihkan, atau ketik **Batal** untuk membatalkan.";
                break;
        }

        if (!empty($confirmationMsg)) {
            session([
                'chatbot_pending_action' => [
                    'user_id' => $user->id,
                    'action' => $action,
                    'action_data' => $actionData,
                    'raw_message' => $rawMessage,
                    'created_at' => time(),
                ]
            ]);

            return [
                'reply' => $confirmationMsg,
                'is_confirmation' => true,
            ];
        }

        return null;
    }

    /**
     * Helper untuk mencocokkan mahasiswa dari teks pesan atau deskripsi transaksi kas
     */
    private function resolveStudentForDues($studentParam, $description, $rawMessage, $user)
    {
        $allUsers = User::all();

        // 1. Cek parameter student dari AI jika ada
        if (!empty($studentParam)) {
            $query = strtolower(trim($studentParam));
            foreach ($allUsers as $u) {
                if (strtolower($u->name) === $query || (string) $u->npm === $query) {
                    return $u;
                }
                if (stripos(strtolower($u->name), $query) !== false || (!empty($u->npm) && stripos((string) $u->npm, $query) !== false)) {
                    return $u;
                }
            }
        }

        // 2. Cocokkan di dalam pesan gabungan
        $combinedText = strtolower($rawMessage . ' ' . $description);
        foreach ($allUsers as $u) {
            $uNameLower = strtolower($u->name);
            $uNpm = (string) $u->npm;

            if ((!empty($uNpm) && stripos($combinedText, $uNpm) !== false) || stripos($combinedText, $uNameLower) !== false) {
                return $u;
            }

            $tokens = preg_split('/\s+/', $uNameLower);
            foreach ($tokens as $tok) {
                if (strlen($tok) >= 3 && preg_match('/\b' . preg_quote($tok, '/') . '\b/i', $combinedText)) {
                    return $u;
                }
            }
        }

        // 3. Jika konteksnya iuran kas tanpa nama lain, anggap untuk user sendiri
        if (preg_match('/\b(?:iuran|bayar kas|kas mingguan|uang kas)\b/i', $combinedText)) {
            return $user;
        }

        return null;
    }

    /**
     * AI Action Reasoner: Mengekstrak perintah bahasa alami dengan penalaran teliti dan koreksi typo
     */
    private function reasonActionWithAi(string $message): array
    {
        $users = User::all(['id', 'name', 'npm', 'role'])->map(function($u) {
            return "{$u->name} [NPM: {$u->npm}, Role: {$u->role}]";
        })->implode('; ');

        $activeSemester = AcademicService::getActiveSemester();

        $recentKas = KasTransaction::where('semester', $activeSemester)
            ->orderBy('id', 'desc')->take(5)->get(['id', 'type', 'amount', 'description'])
            ->map(function($k) {
                return "ID #{$k->id} ({$k->type} Rp " . number_format($k->amount, 0, ',', '.') . " - {$k->description})";
            })->implode('; ');

        $systemPrompt = <<<PROMPT
Kamu adalah AI Action Reasoner untuk Sistem Pengelolaan Kas Kelas UPS Tegal.
Tugas: Analisis pesan pengguna secara teliti dan pahami maksud manusia untuk menghasilkan JSON aksi.

DATA SISTEM REAL-TIME:
- Pengguna: [{$users}]
- Kas Terbaru: [{$recentKas}]
- Semester: {$activeSemester}

PEDOMAN PENALARAN:
1. INTENSI: Jika HANYA BERTANYA / minta panduan / cek ketersediaan data (contoh: "bagaimana cara...", "apakah ada...", "bisa tolong jelaskan...", "apa fungsi..."), kembalikan {"action":"none"}.
2. PENCOCOKAN DATA SISTEM:
   - Untuk ubah role / hapus akun: cari nama/NPM di daftar Pengguna.
   - Untuk kas: konversi nominal angka ("15rb" -> 15000, "50k" -> 50000, "100 ribu" -> 100000).
   - Untuk catat_pemasukan: Jika terkait iuran mahasiswa (misal: "gilang bayar kas", "uang kas lutfi", "iuran tegar", atau menyebut nama/NPM mahasiswa di daftar Pengguna), isi "student" dengan nama lengkap atau NPM mahasiswa tersebut. Jika tidak menyebut nama orang, set "student": null.
   - Untuk batalkan / hapus iuran mahasiswa (misal: "hapus catatan iuran tegar", "batalkan iuran tegar minggu 2", "hapus uang kas tegar", "batalkan kas gilang"): GUNAKAN action "batalkan_iuran" (BUKAN hapus_transaksi). Isi "student" dengan nama mahasiswa, dan isi "week" dengan angka minggu (1-4) jika disebutkan, atau null jika minggu belum disebutkan.
   - Untuk hapus_transaksi / edit_transaksi: Hanya untuk transaksi kas umum. Jika tidak ada angka nomor ID transaksi yang dicantumkan (contoh: "hapus transaksi", "hapus kas", "edit transaksi"), isi "id": null.
   - Untuk ubah semester (misal: "ubah semester aktif ke Semester 6", "ganti semester 4", "ubah semester"): GUNAKAN action "ubah_semester". Isi "semester" dengan string semester atau null jika belum dicantumkan.
3. AKSI YANG DIDUKUNG:
   - batalkan_iuran: {action:"batalkan_iuran", student:string, week:int|null}
   - catat_pemasukan: {action:"catat_pemasukan", amount:int, description:string, student:string|null}
   - catat_pengeluaran: {action:"catat_pengeluaran", amount:int, description:string}
   - edit_transaksi: {action:"edit_transaksi", id:int|null, amount:int|null, description:string|null}
   - hapus_transaksi: {action:"hapus_transaksi", id:int|null}
   - pulihkan_transaksi: {action:"pulihkan_transaksi", id:int|null}
   - ubah_role: {action:"ubah_role", target_user:string|null, new_role:"admin"|"bendahara"|"ketua"|"mahasiswa"}
   - tambah_akun: {action:"tambah_akun", name:string, npm:string, role:string}
   - hapus_akun: {action:"hapus_akun", target_user:string|null}
   - ubah_semester: {action:"ubah_semester", semester:string|null}
   - atur_tarif_iuran: {action:"atur_tarif_iuran", amount:int}
   - none: {action:"none"}

HANYA kembalikan 1 string JSON murni tanpa markdown, tanpa pengantar, tanpa penjelasan di luar JSON.
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $message],
        ];

        $resp = $this->callAiWithFallback($messages, 0.0, 250);
        if ($resp['success']) {
            $raw = $resp['reply'];
            if (preg_match('/\{.*\}/s', $raw, $m)) {
                $json = json_decode($m[0], true);
                if (is_array($json) && isset($json['action'])) {
                    return $json;
                }
            }
        }

        return ['action' => 'none'];
    }

    /**
     * Eksekusi aksi yang diekstrak oleh AI Reasoner dengan validasi otorisasi dan data
     */
    private function executeParsedAction(array $actionData, $user, $bendaharaName, $ketuaName, $adminName, $userRoleLabel, string $rawMessage = '')
    {
        if (isset($actionData['parameters']) && is_array($actionData['parameters'])) {
            foreach ($actionData['parameters'] as $k => $v) {
                if (!isset($actionData[$k])) {
                    $actionData[$k] = $v;
                }
            }
        }

        $action = $actionData['action'] ?? 'none';
        $userRole = $user->role;

        switch ($action) {
            case 'atur_tarif_iuran':
            case 'set_tarif_iuran':
                if ($userRole !== 'bendahara') {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Pengaturan tarif iuran mingguan **hanya berhak dilakukan oleh Bendahara Kelas** ({$bendaharaName}).";
                }
                $amount = (int) ($actionData['amount'] ?? 0);
                if ($amount < 500) {
                    return "❌ GAGAL: Nominal tarif iuran minimal Rp 500. Anda memasukkan: " . $amount;
                }
                AcademicService::setDuesRatePerWeek($amount);
                return "✅ BERHASIL: Tarif iuran kas mingguan berhasil diubah menjadi **Rp " . number_format($amount, 0, ',', '.') . " / minggu** oleh Bendahara ({$user->name})!\n"
                    . "- Seluruh kalkulasi tagihan kas, matriks buku kas, dan tunggakan mahasiswa kini otomatis tersinkronisasi ke tarif baru ini.";

            case 'catat_pemasukan':
                if (!in_array($userRole, ['bendahara', 'admin', 'ketua'])) {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Pencatatan pemasukan kas hanya berhak dilakukan oleh Bendahara Kelas, Super Admin, atau Ketua Kelas.";
                }
                $amount = (float) ($actionData['amount'] ?? 0);
                $desc = (string) ($actionData['description'] ?? 'Pemasukan kas');
                $student = isset($actionData['student']) ? (string) $actionData['student'] : null;
                return $this->actionCatatTransaksi($user, 'pemasukan', $amount, $desc, $student, $rawMessage);

            case 'catat_pengeluaran':
                if (!in_array($userRole, ['bendahara', 'admin', 'ketua'])) {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Pencatatan pengeluaran kas hanya berhak dilakukan oleh Bendahara Kelas, Super Admin, atau Ketua Kelas.";
                }
                $amount = (float) ($actionData['amount'] ?? 0);
                $desc = (string) ($actionData['description'] ?? 'Pengeluaran kas');
                return $this->actionCatatTransaksi($user, 'pengeluaran', $amount, $desc, null, $rawMessage);

            case 'batalkan_iuran':
            case 'hapus_iuran':
                if ($userRole !== 'bendahara') {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Pembatalan catatan iuran kas mahasiswa **hanya berhak dilakukan oleh Bendahara Kelas** ({$bendaharaName}).";
                }
                return $this->actionBatalkanIuran($user, $actionData);

            case 'hapus_transaksi':
                if ($userRole !== 'bendahara') {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Penghapusan transaksi kas **hanya berhak dilakukan oleh Bendahara Kelas** ({$bendaharaName}).";
                }
                $id = (int) ($actionData['id'] ?? 0);
                return $this->actionHapusTransaksi($user, $id);

            case 'pulihkan_transaksi':
                if ($userRole !== 'bendahara') {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Pemulihan transaksi kas terhapus **hanya berhak dilakukan oleh Bendahara Kelas** ({$bendaharaName}).";
                }
                $id = (int) ($actionData['id'] ?? 0);
                return $this->actionPulihkanTransaksi($user, $id);

            case 'edit_transaksi':
                if ($userRole !== 'bendahara') {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Pengubahan transaksi kas **hanya berhak dilakukan oleh Bendahara Kelas** ({$bendaharaName}).";
                }
                $id = (int) ($actionData['id'] ?? 0);
                $amount = isset($actionData['amount']) ? (string) $actionData['amount'] : null;
                $desc = isset($actionData['description']) ? (string) $actionData['description'] : null;
                return $this->actionEditTransaksi($user, $id, $amount, $desc);

            case 'ubah_role':
                if (!in_array($userRole, ['admin', 'ketua'])) {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Pengubahan hak akses role akun hanya berhak dilakukan oleh Super Admin atau Ketua Kelas.";
                }
                $target = (string) ($actionData['target_user'] ?? '');
                $role = (string) ($actionData['new_role'] ?? $actionData['role'] ?? 'mahasiswa');
                return $this->actionUbahRole($user, $target, $role);

            case 'tambah_akun':
                if (!in_array($userRole, ['admin', 'ketua'])) {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Penambahan akun pengguna baru hanya berhak dilakukan oleh Super Admin atau Ketua Kelas.";
                }
                $name = (string) ($actionData['name'] ?? '');
                $npm = (string) ($actionData['npm'] ?? '');
                $role = (string) ($actionData['role'] ?? 'mahasiswa');
                return $this->actionTambahAkun($user, $name, $npm, $role);

            case 'hapus_akun':
                if (!in_array($userRole, ['admin', 'ketua'])) {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Penghapusan akun pengguna hanya berhak dilakukan oleh Super Admin atau Ketua Kelas.";
                }
                $target = (string) ($actionData['target_user'] ?? '');
                return $this->actionHapusAkun($user, $target);

            case 'ubah_semester':
                if (!in_array($userRole, ['admin', 'ketua'])) {
                    return "❌ AKSES DITOLAK: Anda login sebagai **{$userRoleLabel}**. Pengaturan semester kelas hanya berhak dilakukan oleh Super Admin atau Ketua Kelas.";
                }
                $sem = (string) ($actionData['semester'] ?? '');
                return $this->actionSetSemester($sem, $user);

            default:
                return null;
        }
    }

    // Eksekusi aksi Super Admin (role, akun, semester)
    private function actionUbahRole($user, $targetQuery, $newRoleRaw)
    {
        $roleMap = [
            'admin' => 'admin',
            'super admin' => 'admin',
            'bendahara' => 'bendahara',
            'ketua' => 'ketua',
            'mahasiswa' => 'mahasiswa',
        ];

        $newRole = $roleMap[strtolower($newRoleRaw)] ?? null;
        if (!$newRole) {
            return "❌ GAGAL: Role '{$newRoleRaw}' tidak valid. Pilihan: admin, bendahara, ketua, mahasiswa.";
        }

        $target = User::where('npm', $targetQuery)
            ->orWhere('name', 'like', "%{$targetQuery}%")
            ->first();

        if (!$target) {
            return "❌ GAGAL: Akun pengguna dengan nama atau NPM \"{$targetQuery}\" tidak ditemukan.";
        }

        $oldRoleLabel = $this->getRoleLabel($target->role);
        $newRoleLabel = $this->getRoleLabel($newRole);

        $target->role = $newRole;
        $target->save();

        AuditLog::record(
            'ubah_role',
            "Mengubah peran akun {$target->name} (NPM: {$target->npm}) dari {$oldRoleLabel} menjadi {$newRoleLabel} via AI Chatbot",
            $user,
            'chatbot',
            ['user_id' => $target->id, 'new_role' => $newRole]
        );

        return "✅ BERHASIL diubah oleh Super Admin ({$user->name})!\n"
            . "- Pengguna: {$target->name} (NPM: {$target->npm})\n"
            . "- Peran Lama: {$oldRoleLabel}\n"
            . "- Peran Baru: **{$newRoleLabel}**\n"
            . "- Hak akses pengguna ini telah langsung disesuaikan di sistem.";
    }

    private function actionTambahAkun($user, $name, $npm, $roleRaw)
    {
        $roleMap = [
            'admin' => 'admin',
            'super admin' => 'admin',
            'bendahara' => 'bendahara',
            'ketua' => 'ketua',
            'mahasiswa' => 'mahasiswa',
        ];
        $role = $roleMap[strtolower($roleRaw)] ?? 'mahasiswa';

        if (empty($name) || empty($npm)) {
            return "❌ GAGAL: Nama dan NPM harus diisi.";
        }

        if (User::where('npm', $npm)->exists()) {
            return "❌ GAGAL: Akun dengan NPM {$npm} sudah terdaftar di sistem.";
        }

        // Proteksi Duplikasi Akun Berdasarkan Nama Sama Persis
        $normalizedName = strtolower(trim($name));
        $existingNameUser = User::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])->first();
        if ($existingNameUser) {
            return "❌ GAGAL: Mahasiswa dengan nama '{$existingNameUser->name}' sudah terdaftar dalam sistem (NPM: {$existingNameUser->npm}, Role: {$existingNameUser->role}). Untuk menghindari akun ganda, gunakan nama yang berbeda atau berikan inisial pembeda.";
        }

        $activeClassSemester = AcademicService::getActiveSemester();

        $newUser = User::create([
            'name' => ucwords(trim($name)),
            'npm' => trim($npm),
            'email' => trim($npm) . '@mail.upstegal.ac.id',
            'password' => Hash::make(trim($npm)),
            'role' => $role,
            'semester' => $activeClassSemester,
            'status_akademik' => 'Aktif',
            'tahun_ajaran' => AcademicService::getTahunAjaran($activeClassSemester),
        ]);

        AuditLog::record(
            'tambah_akun',
            "Mendaftarkan akun baru {$newUser->name} (NPM: {$newUser->npm}) role {$role} via AI Chatbot",
            $user,
            'chatbot',
            ['user_id' => $newUser->id, 'npm' => $newUser->npm, 'role' => $role, 'semester' => $activeClassSemester]
        );

        return "✅ BERHASIL ditambahkan oleh Super Admin ({$user->name})!\n"
            . "- Nama: {$newUser->name}\n"
            . "- NPM: {$newUser->npm}\n"
            . "- Role: **" . $this->getRoleLabel($role) . "**\n"
            . "- Semester: {$activeClassSemester}\n"
            . "- Password Default: [NPM]";
    }

    private function actionHapusAkun($user, $targetQuery)
    {
        $target = User::where('npm', $targetQuery)
            ->orWhere('id', $targetQuery)
            ->orWhere('name', 'like', "%{$targetQuery}%")
            ->first();

        if (!$target) {
            return "❌ GAGAL: Akun \"{$targetQuery}\" tidak ditemukan.";
        }

        if ($target->id === $user->id) {
            return "❌ GAGAL: Anda tidak dapat menghapus akun Anda sendiri.";
        }

        $targetInfo = "{$target->name} (NPM: {$target->npm}, Role: " . $this->getRoleLabel($target->role) . ")";
        
        DuesPayment::where('user_id', $target->id)->delete();
        $target->delete();

        AuditLog::record(
            'hapus_akun',
            "Menghapus akun {$targetInfo} via AI Chatbot",
            $user,
            'chatbot',
            ['npm' => $target->npm, 'name' => $target->name, 'role' => $target->role]
        );

        return "✅ BERHASIL dihapus oleh Super Admin ({$user->name})!\n- Akun dihapus: **{$targetInfo}**";
    }

    private function actionSetSemester($targetSemesterRaw, $user)
    {
        if (preg_match('/(\d+)/', (string) $targetSemesterRaw, $m)) {
            $targetSemester = "Semester " . $m[1];
        } else {
            $targetSemester = ucwords(strtolower(trim((string) $targetSemesterRaw)));
        }

        if (!in_array($targetSemester, AcademicService::SEMESTERS)) {
            return "❌ GAGAL: Pilihan semester tidak valid. Pilihan: " . implode(', ', AcademicService::SEMESTERS);
        }

        AcademicService::setActiveSemester($targetSemester);
        User::where('role', 'admin')->update(['semester' => $targetSemester]);
        session(['selected_semester' => $targetSemester]);

        $kelasLabel = AcademicService::getKelasLabel($targetSemester);

        AuditLog::record(
            'ubah_semester_aktif',
            "Mengubah semester aktif kelas ke {$targetSemester} (Kelas {$kelasLabel}) via AI Chatbot",
            $user,
            'chatbot',
            ['semester' => $targetSemester]
        );

        return "✅ BERHASIL: Semester aktif kelas berhasil diubah ke **{$targetSemester}** (Kelas {$kelasLabel}) oleh Super Admin ({$user->name}).\n"
            . "- Data pembukuan kas kelas otomatis beralih ke periode semester ini.";
    }

    // Eksekusi aksi Bendahara (mutasi kas, tarif, iuran)
    private function actionCatatTransaksi($user, $type, $amountStr, $description, $studentParam = null, $rawMessage = '')
    {
        $amount = (float) str_replace(['.', ','], ['', '.'], (string) $amountStr);
        $description = ucfirst(trim((string) $description));
        if (empty($description)) {
            $description = "Transaksi kas";
        }
        $activeClassSemester = AcademicService::getActiveSemester();

        if ($amount < 500) {
            return "❌ GAGAL: Nominal minimal Rp 500. Anda memasukkan Rp " . number_format($amount, 0, ',', '.');
        }

        if ($type === 'pengeluaran') {
            $currentIn = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
            $currentOut = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
            $currentBalance = $currentIn - $currentOut;

            if ($amount > $currentBalance) {
                return "❌ GAGAL: Pengeluaran Rp " . number_format($amount, 0, ',', '.') . " melebihi saldo aktif Rp " . number_format($currentBalance, 0, ',', '.') . ". Saldo kas tidak boleh minus.";
            }
        }

        $targetStudent = null;
        $duesWeeksPaid = [];
        $currentMonth = (int) now()->month;
        $currentYear = (int) now()->year;
        $activeWeek = min(4, (int) ceil(now()->day / 7));

        // Sinkronisasi waktu: deteksi jika pengguna menyebut bulan tertentu, jika tidak gunakan bulan real-time saat ini
        $bulanMap = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
            'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
            'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
        ];
        foreach ($bulanMap as $bName => $bNum) {
            if (preg_match('/\b(?:bulan\s+|bln\s+)?' . $bName . '\b/i', $rawMessage)) {
                $currentMonth = $bNum;
                break;
            }
        }
        if (preg_match('/\b(?:bulan|bln)\s*(\d{1,2})\b/i', $rawMessage, $mMatches)) {
            $parsedM = (int) $mMatches[1];
            if ($parsedM >= 1 && $parsedM <= 12) {
                $currentMonth = $parsedM;
            }
        }

        if ($type === 'pemasukan') {
            $allUsers = User::all();

            // 1. Cek parameter student dari AI Reasoner jika ada
            if (!empty($studentParam)) {
                $query = strtolower(trim($studentParam));
                foreach ($allUsers as $u) {
                    if (strtolower($u->name) === $query || (string) $u->npm === $query) {
                        $targetStudent = $u;
                        break;
                    }
                    if (stripos(strtolower($u->name), $query) !== false || (!empty($u->npm) && stripos((string) $u->npm, $query) !== false)) {
                        $targetStudent = $u;
                        break;
                    }
                }
            }

            // 2. Jika belum ditemukan, periksa pesan teks asli dan deskripsi transaksi
            if (!$targetStudent) {
                $combinedText = strtolower($rawMessage . ' ' . $description);
                foreach ($allUsers as $u) {
                    $uNameLower = strtolower($u->name);
                    $uNpm = (string) $u->npm;

                    // Cocokkan NPM atau Nama Lengkap
                    if ((!empty($uNpm) && stripos($combinedText, $uNpm) !== false) || stripos($combinedText, $uNameLower) !== false) {
                        $targetStudent = $u;
                        break;
                    }

                    // Cocokkan per token nama (minimal 3 karakter, misal "gilang", "lutfi", "azizah", "tegar")
                    $tokens = preg_split('/\s+/', $uNameLower);
                    foreach ($tokens as $tok) {
                        if (strlen($tok) >= 3 && preg_match('/\b' . preg_quote($tok, '/') . '\b/i', $combinedText)) {
                            $targetStudent = $u;
                            break 2;
                        }
                    }
                }
            }

            // 3. Jika tetap tidak menyebut nama orang lain, tetapi konteksnya iuran kas
            if (!$targetStudent) {
                $combinedText = strtolower($rawMessage . ' ' . $description);
                if (preg_match('/\b(?:iuran|bayar kas|kas mingguan|uang kas)\b/i', $combinedText)) {
                    $targetStudent = $user; // Iuran untuk pengguna yang sedang login
                }
            }
        }

        // Tentukan user_id transaksi kas
        if ($type === 'pemasukan' && $targetStudent) {
            $txUserId = $targetStudent->id;

            // Hitung minggu yang belum lunas
            $paidWeeks = DuesPayment::where('user_id', $targetStudent->id)
                ->where('semester', $activeClassSemester)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->where('status', 'lunas')
                ->pluck('week_number')
                ->map(fn($w) => (int) $w)
                ->toArray();

            $unpaidWeeks = array_values(array_diff(range(1, 4), $paidWeeks));
            $duesRate = AcademicService::getDuesRatePerWeek();
            $weeksCoveredCount = max(1, min(4, (int) round($amount / $duesRate)));
            $duesWeeksPaid = !empty($unpaidWeeks) ? array_slice($unpaidWeeks, 0, $weeksCoveredCount) : range(1, $weeksCoveredCount);

            // Format keterangan standar seperti pada buku kas web
            $weeksLabel = implode(', ', array_map(fn($w) => "Mg {$w}", $duesWeeksPaid));
            $description = "Iuran Kas: {$targetStudent->name} ({$weeksLabel} Bln {$currentMonth}/{$currentYear} - {$activeClassSemester})";
        } else {
            $txUserId = $user->id;
        }

        $transactionData = [
            'user_id' => $txUserId,
            'type' => $type,
            'amount' => $amount,
            'date' => Carbon::today()->format('Y-m-d'),
            'description' => "[Via AI] " . $description,
            'semester' => $activeClassSemester,
            'tahun_ajaran' => AcademicService::getTahunAjaran($activeClassSemester),
        ];

        // Proteksi Duplikasi Transaksi Kas via AI Chatbot
        $todayDate = Carbon::today()->format('Y-m-d');
        $duplicateCheck = KasTransaction::where('semester', $activeClassSemester)
            ->where('type', $type)
            ->where('amount', $amount)
            ->whereDate('date', $todayDate)
            ->where('user_id', $txUserId)
            ->whereRaw('LOWER(TRIM(description)) = ?', [strtolower(trim($transactionData['description']))])
            ->first();

        if ($duplicateCheck) {
            return "⚠️ **Transaksi Ganda Ditolak!**\n\n"
                . "Transaksi {$type} sebesar **Rp " . number_format($amount, 0, ',', '.') . "** dengan rincian yang sama persis sudah tercatat hari ini (ID Transaksi #{$duplicateCheck->id}).\n"
                . "Sistem membatalkan pencatatan kedua untuk menjaga ketepatan saldo kas kelas.";
        }

        $transaction = KasTransaction::create($transactionData);

        // Sinkronisasi status DuesPayment menjadi lunas
        if ($type === 'pemasukan' && $targetStudent && !empty($duesWeeksPaid)) {
            foreach ($duesWeeksPaid as $wk) {
                DuesPayment::updateOrCreate(
                    [
                        'user_id' => $targetStudent->id,
                        'week_number' => $wk,
                        'month' => $currentMonth,
                        'year' => $currentYear,
                        'semester' => $activeClassSemester,
                    ],
                    [
                        'kas_transaction_id' => $transaction->id,
                        'status' => 'lunas',
                    ]
                );
            }
        }

        $newIn = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
        $newOut = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
        $newSaldo = $newIn - $newOut;

        $label = $type === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran';
        $reply = "✅ BERHASIL dicatat oleh Bendahara ({$user->name})!\n"
            . "- Transaksi: #{$transaction->id} | {$label}: **Rp " . number_format($amount, 0, ',', '.') . "**\n"
            . "- Keterangan: {$description}\n"
            . "- Periode: {$activeClassSemester} (" . AcademicService::getTahunAjaran($activeClassSemester) . ")\n"
            . "- Saldo Kas Aktif Sekarang: **Rp " . number_format($newSaldo, 0, ',', '.') . "**";

        AuditLog::record(
            'catat_transaksi',
            "Mencatat {$type} sebesar Rp " . number_format($amount, 0, ',', '.') . " ({$description}) via AI Chatbot",
            $user,
            'chatbot',
            ['id' => $transaction->id, 'type' => $type, 'amount' => $amount, 'semester' => $activeClassSemester]
        );

        if (!empty($proofImage)) {
            $reply .= "\n- Bukti Foto/Nota: **Tersimpan resmi** (Dapat dilihat di arsip & buku kas)";
        }

        if ($targetStudent && !empty($duesWeeksPaid)) {
            $reply .= "\n- Iuran Mahasiswa: **{$targetStudent->name}** (NPM: {$targetStudent->npm})\n"
                . "- Status Iuran: **LUNAS Minggu ke-" . implode(', ', $duesWeeksPaid) . "** (Tersinkronisasi ke Matriks Buku Kas & Dashboard Mahasiswa)";
        } else {
            $reply .= "\n- Data langsung tersinkronisasi ke seluruh sistem buku kas.";
        }

        return $reply;
    }

    /**
     * Membatalkan catatan iuran kas mingguan mahasiswa secara aman dan atomik
     */
    private function actionBatalkanIuran($user, array $actionData)
    {
        $dueId = (int) ($actionData['due_id'] ?? 0);
        $studentId = (int) ($actionData['student_id'] ?? 0);
        $week = (int) ($actionData['week'] ?? 0);

        $student = $studentId > 0 ? User::find($studentId) : null;
        $due = $dueId > 0 ? DuesPayment::find($dueId) : null;

        if (!$due && $studentId > 0 && $week > 0) {
            $activeClassSemester = AcademicService::getActiveSemester();
            $targetMonth = (int) now()->month;
            $targetYear = (int) now()->year;
            $bulanMap = [
                'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
                'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
                'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12
            ];
            $rawMsg = strtolower($actionData['raw_message'] ?? '');
            foreach ($bulanMap as $bName => $bNum) {
                if (preg_match('/\b(?:bulan\s+|bln\s+)?' . $bName . '\b/i', $rawMsg)) {
                    $targetMonth = $bNum;
                    break;
                }
            }

            // Prioritaskan pencarian pada bulan berjalan real-time
            $due = DuesPayment::where('user_id', $studentId)
                ->where('week_number', $week)
                ->where('semester', $activeClassSemester)
                ->where('month', $targetMonth)
                ->where('year', $targetYear)
                ->where('status', 'lunas')
                ->first();

            // Jika tidak ditemukan di bulan berjalan, ambil catatan lunas terakhir
            if (!$due) {
                $due = DuesPayment::where('user_id', $studentId)
                    ->where('week_number', $week)
                    ->where('semester', $activeClassSemester)
                    ->where('status', 'lunas')
                    ->latest('id')
                    ->first();
            }
        }

        if (!$due) {
            return "❌ Gagal: Data catatan iuran tidak ditemukan atau sudah dibatalkan sebelumnya.";
        }

        $studentName = $student ? "{$student->name} (NPM: {$student->npm})" : ($due->user ? "{$due->user->name} (NPM: {$due->user->npm})" : "Mahasiswa");
        $activeClassSemester = $due->semester ?? AcademicService::getActiveSemester();
        $rate = AcademicService::getDuesRatePerWeek();
        $weekNumber = $due->week_number;
        $periodMonth = $due->month;
        $periodYear = $due->year;

        DB::transaction(function () use ($due, $rate) {
            $txId = $due->kas_transaction_id;
            $due->delete();

            if ($txId) {
                $otherDuesCount = DuesPayment::where('kas_transaction_id', $txId)->count();
                if ($otherDuesCount === 0) {
                    KasTransaction::where('id', $txId)->delete();
                } else {
                    $tx = KasTransaction::find($txId);
                    if ($tx && $tx->amount > $rate) {
                        $tx->decrement('amount', $rate);
                    }
                }
            }
        });

        $newIn = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
        $newOut = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
        $newSaldo = $newIn - $newOut;

        AuditLog::record(
            'batal_iuran',
            "Membatalkan status iuran Minggu ke-{$weekNumber} mahasiswa {$studentName} (Bln {$periodMonth}/{$periodYear}) via AI Chatbot",
            $user,
            'chatbot',
            ['student_id' => $studentId, 'week' => $weekNumber, 'semester' => $activeClassSemester]
        );

        return "✅ BERHASIL dibatalkan oleh Bendahara ({$user->name})!\n"
            . "- Mahasiswa: **{$studentName}**\n"
            . "- Catatan Iuran: **Minggu ke-{$weekNumber}** (Bulan {$periodMonth}/{$periodYear}) berhasil dibatalkan\n"
            . "- Periode: {$activeClassSemester} (" . AcademicService::getTahunAjaran($activeClassSemester) . ")\n"
            . "- Status: Kembali belum lunas / tertunggak\n"
            . "- Saldo Kas Aktif Sekarang: **Rp " . number_format($newSaldo, 0, ',', '.') . "**\n"
            . "- Seluruh matriks buku kas dan dashboard mahasiswa langsung tersinkronisasi.";
    }

    private function actionHapusTransaksi($user, $txId)
    {
        $tx = null;
        if ($txId && $txId > 0) {
            $tx = KasTransaction::find($txId);
        }
        if (!$tx) {
            $activeClassSemester = AcademicService::getActiveSemester();
            $tx = KasTransaction::where('semester', $activeClassSemester)->orderBy('id', 'desc')->first();
        }

        if (!$tx) {
            return "⚠️ GAGAL: Transaksi kas dengan ID #" . ($txId ?: 'terbaru') . " tidak ditemukan.";
        }

        $activeClassSemester = AcademicService::getActiveSemester();

        // Rekam snapshot lengkap sebelum soft-delete (foto bukti tetap dipertahankan untuk pemulihan)
        $relatedDues = DuesPayment::where('kas_transaction_id', $tx->id)->get()->map(function ($d) {
            return [
                'id' => $d->id,
                'user_id' => $d->user_id,
                'week_number' => $d->week_number,
                'month' => $d->month,
                'year' => $d->year,
                'semester' => $d->semester,
            ];
        })->toArray();

        $beforeSnapshot = [
            'id' => $tx->id,
            'user_id' => $tx->user_id,
            'type' => $tx->type,
            'amount' => (float) $tx->amount,
            'date' => $tx->date ? $tx->date->format('Y-m-d') : null,
            'description' => $tx->description,
            'semester' => $tx->semester,
            'proof_image' => $tx->proof_image,
            'related_dues' => $relatedDues,
        ];

        $info = ucfirst($tx->type) . " Rp " . number_format($tx->amount, 0, ',', '.') . " ({$tx->description})";
        $deletedId = $tx->id;

        DB::transaction(function () use ($tx) {
            DuesPayment::where('kas_transaction_id', $tx->id)->delete(); // SoftDelete
            $tx->delete(); // SoftDelete
        });

        AuditLog::record(
            'hapus_transaksi',
            "Menghapus transaksi #{$deletedId} ({$info}) via AI Chatbot [Soft Delete / Backup tersimpan]",
            $user,
            'chatbot',
            [
                'id' => $deletedId,
                'semester' => $tx->semester,
                'before' => $beforeSnapshot,
            ]
        );

        $newIn = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
        $newOut = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
        $newSaldo = $newIn - $newOut;

        return "✅ BERHASIL dihapus oleh Bendahara ({$user->name})!\n"
            . "- Dihapus: Transaksi #{$deletedId} ({$info})\n"
            . "- Saldo Kas Aktif Terbaru: **Rp " . number_format($newSaldo, 0, ',', '.') . "**\n"
            . "- Backup tersimpan aman (*Soft Delete*). Ketik **`pulihkan transaksi #{$deletedId}`** kapan saja jika ingin mengembalikannya.";
    }

    private function listDeletedTransactions($user): string
    {
        if (!$user || $user->role !== 'bendahara') {
            return "❌ **Akses Ditolak**: Fitur backup & pemulihan transaksi kas **hanya berhak diakses oleh Bendahara Kelas**.";
        }

        $trashed = KasTransaction::onlyTrashed()->latest('deleted_at')->take(15)->get();

        if ($trashed->isEmpty()) {
            return "♻️ **Daftar Backup Transaksi Kas Terhapus**\n\n"
                . "Saat ini belum ada transaksi kas yang berada di daftar backup/terhapus.\n"
                . "Setiap transaksi yang dihapus oleh Bendahara (baik via form web maupun AI Chatbot) otomatis tersimpan aman di sini agar dapat dipulihkan sewaktu-waktu.";
        }

        $reply = "♻️ **Daftar Backup Transaksi Kas Terhapus** (Khusus Bendahara)\n\n"
            . "Berikut transaksi yang tersimpan di backup (*Soft Delete*) dan masih dapat dipulihkan:\n\n";

        foreach ($trashed as $idx => $tx) {
            $no = $idx + 1;
            $typeLabel = ucfirst($tx->type);
            $nominal = number_format($tx->amount, 0, ',', '.');
            $txDate = $tx->date ? $tx->date->format('d/m/Y') : '-';
            $delDate = $tx->deleted_at ? $tx->deleted_at->format('d/m/Y H:i') : '-';
            $duesCount = DuesPayment::onlyTrashed()->where('kas_transaction_id', $tx->id)->count();
            $duesTag = $duesCount > 0 ? " | +{$duesCount} iuran mingguan" : "";

            $reply .= "{$no}. **ID #{$tx->id}** — {$typeLabel} **Rp {$nominal}**\n"
                . "   • Keterangan: {$tx->description}\n"
                . "   • Tanggal: {$txDate} ({$tx->semester}{$duesTag}) | Dihapus: {$delDate} WIB\n";
        }

        $firstId = $trashed->first()->id;
        $reply .= "\nUntuk mengembalikan transaksi ke pembukuan aktif, ketik perintah:\n"
            . "• **`pulihkan transaksi #{$firstId}`** (ganti nomor ID sesuai daftar di atas)";

        return $reply;
    }

    private function actionPulihkanTransaksi($user, $txId)
    {
        if (!$user || $user->role !== 'bendahara') {
            return "❌ AKSES DITOLAK: Pemulihan transaksi kas terhapus **hanya berhak dilakukan oleh Bendahara Kelas**.";
        }

        $tx = $txId > 0 ? KasTransaction::onlyTrashed()->find($txId) : null;
        if (!$tx) {
            return "⚠️ GAGAL: Transaksi terhapus dengan ID #{$txId} tidak ditemukan di daftar backup.";
        }

        // Cek kecukupan saldo jika transaksi yang dipulihkan bertipe pengeluaran
        if ($tx->type === 'pengeluaran') {
            $currentIn = KasTransaction::where('semester', $tx->semester)->where('type', 'pemasukan')->sum('amount');
            $currentOut = KasTransaction::where('semester', $tx->semester)->where('type', 'pengeluaran')->sum('amount');
            $available = $currentIn - $currentOut;
            if ((float) $tx->amount > $available) {
                return "❌ GAGAL: Pemulihan pengeluaran Rp " . number_format($tx->amount, 0, ',', '.') . " melebihi saldo kas tersedia pada {$tx->semester} (Rp " . number_format($available, 0, ',', '.') . ").";
            }
        }

        $restoredWeeks = [];
        DB::transaction(function () use ($tx, &$restoredWeeks) {
            $tx->restore();

            $trashedDues = DuesPayment::onlyTrashed()->where('kas_transaction_id', $tx->id)->get();
            foreach ($trashedDues as $due) {
                $existsActive = DuesPayment::where('user_id', $due->user_id)
                    ->where('week_number', $due->week_number)
                    ->where('month', $due->month)
                    ->where('year', $due->year)
                    ->where('semester', $due->semester)
                    ->exists();
                if (!$existsActive) {
                    $due->restore();
                    $restoredWeeks[] = "Mg {$due->week_number} ({$due->month}/{$due->year})";
                }
            }
        });

        $info = ucfirst($tx->type) . " Rp " . number_format($tx->amount, 0, ',', '.') . " ({$tx->description})";
        AuditLog::record(
            'pulihkan_transaksi',
            "Memulihkan transaksi #{$tx->id} ({$info}) dari backup via AI Chatbot",
            $user,
            'chatbot',
            [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => (float) $tx->amount,
                'semester' => $tx->semester,
                'restored_dues' => $restoredWeeks,
            ]
        );

        $activeClassSemester = AcademicService::getActiveSemester();
        $newIn = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
        $newOut = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
        $newSaldo = $newIn - $newOut;

        $duesInfo = !empty($restoredWeeks)
            ? "\n- Iuran Dipulihkan: **" . implode(', ', $restoredWeeks) . "**"
            : "";

        return "✅ BERHASIL dipulihkan oleh Bendahara ({$user->name})!\n"
            . "- Dipulihkan: Transaksi **#{$tx->id}** ({$info}){$duesInfo}\n"
            . "- Saldo Kas Aktif ({$activeClassSemester}): **Rp " . number_format($newSaldo, 0, ',', '.') . "**\n"
            . "- Data Buku Kas, Arsip, dan status iuran mahasiswa langsung tersinkronisasi.";
    }

    private function actionEditTransaksi($user, $txId, $newAmountStr, $newDescription)
    {
        $tx = null;
        if ($txId && $txId > 0) {
            $tx = KasTransaction::find($txId);
        }
        if (!$tx) {
            $activeClassSemester = AcademicService::getActiveSemester();
            $tx = KasTransaction::where('semester', $activeClassSemester)->orderBy('id', 'desc')->first();
        }

        if (!$tx) {
            return "⚠️ GAGAL: Transaksi kas dengan ID #" . ($txId ?: 'terbaru') . " tidak ditemukan.";
        }

        // Rekam snapshot data lama (before) sebagai backup audit sebelum diubah
        $beforeSnapshot = [
            'type' => $tx->type,
            'amount' => (float) $tx->amount,
            'date' => $tx->date ? $tx->date->format('Y-m-d') : null,
            'description' => $tx->description,
            'semester' => $tx->semester,
            'proof_image' => $tx->proof_image,
        ];

        $changes = [];

        if ($newAmountStr !== null) {
            $newAmount = (float) str_replace(['.', ','], ['', '.'], (string) $newAmountStr);
            if ($newAmount < 500) {
                return "❌ GAGAL: Nominal minimal adalah Rp 500.";
            }

            if ($tx->type === 'pengeluaran') {
                $activeClassSemester = AcademicService::getActiveSemester();
                $currentIn = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
                $currentOutWithoutThis = KasTransaction::where('semester', $activeClassSemester)
                    ->where('type', 'pengeluaran')
                    ->where('id', '!=', $tx->id)
                    ->sum('amount');
                $available = $currentIn - $currentOutWithoutThis;

                if ($newAmount > $available) {
                    return "❌ GAGAL: Nominal Rp " . number_format($newAmount, 0, ',', '.') . " melebihi saldo kas tersedia Rp " . number_format($available, 0, ',', '.');
                }
            }

            $changes[] = "Nominal: Rp " . number_format($tx->amount, 0, ',', '.') . " ➔ Rp " . number_format($newAmount, 0, ',', '.');
            $tx->amount = $newAmount;
        }

        if ($newDescription !== null && !empty($newDescription)) {
            $cleanDesc = ucfirst(trim($newDescription));
            $changes[] = "Keterangan: \"{$tx->description}\" ➔ \"{$cleanDesc}\"";
            $tx->description = "[Via AI] " . $cleanDesc;

            if ($tx->type === 'pemasukan') {
                $allUsers = User::all();
                foreach ($allUsers as $u) {
                    if (stripos($cleanDesc, $u->name) !== false || (!empty($u->npm) && stripos($cleanDesc, (string) $u->npm) !== false)) {
                        $tx->user_id = $u->id;
                        DuesPayment::where('kas_transaction_id', $tx->id)->update(['user_id' => $u->id]);
                        $changes[] = "Siswa Terkait: {$u->name} (NPM: {$u->npm})";
                        break;
                    }
                }
            }
        }

        if (empty($changes)) {
            return "ℹ️ Tidak ada perubahan data pada transaksi #{$tx->id}.";
        }

        $tx->save();

        $afterSnapshot = [
            'type' => $tx->type,
            'amount' => (float) $tx->amount,
            'date' => $tx->date ? $tx->date->format('Y-m-d') : null,
            'description' => $tx->description,
            'semester' => $tx->semester,
            'proof_image' => $tx->proof_image,
        ];

        AuditLog::record(
            'edit_transaksi',
            "Mengubah transaksi #{$tx->id} (" . implode(" | ", $changes) . ") via AI Chatbot",
            $user,
            'chatbot',
            [
                'id' => $tx->id,
                'changes' => $changes,
                'semester' => $tx->semester,
                'before' => $beforeSnapshot,
                'after' => $afterSnapshot,
            ]
        );

        return "✅ BERHASIL diedit oleh Bendahara ({$user->name})!\n"
            . "- Transaksi: #{$tx->id}\n"
            . "- Perubahan: " . implode(" | ", $changes) . "\n"
            . "- Snapshot data lama (*before*) otomatis tersimpan di Audit Log.";
    }





    /**
     * Bangun system prompt padat, ringkas, dan fokus dengan guardrails
     */
    private function buildSystemContext($user)
    {
        $activeClassSemester = AcademicService::getActiveSemester();
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
        $tahunAjaran = AcademicService::getTahunAjaran($activeClassSemester);
        $today = Carbon::now();
        $currentMonth = (int) $today->month;
        $currentYear = (int) $today->year;
        $activeWeek = min(4, (int) ceil($today->day / 7));
        $namaBulan = AcademicService::getNamaBulan($currentMonth);

        // 1. DATA USER & STRUKTUR PENGURUS
        $allUsers = User::orderBy('role')->orderBy('npm')->get();
        $adminUsers = $allUsers->where('role', 'admin');
        $ketuaUsers = $allUsers->where('role', 'ketua');
        $bendaharaUsers = $allUsers->where('role', 'bendahara');

        $adminName = $adminUsers->map(fn($u) => "{$u->name} ({$u->npm})")->implode(', ') ?: 'Belum ada';
        $ketuaName = $ketuaUsers->map(fn($u) => "{$u->name} ({$u->npm})")->implode(', ') ?: 'Belum ada';
        $bendaharaName = $bendaharaUsers->map(fn($u) => "{$u->name} ({$u->npm})")->implode(', ') ?: 'Belum ada';

        // 2. DATA KAS KELAS
        $duesRate = AcademicService::getDuesRatePerWeek();
        $pemasukanSemester = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
        $pengeluaranSemester = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
        $saldoSemester = $pemasukanSemester - $pengeluaranSemester;

        $totalPemasukanAll = KasTransaction::where('type', 'pemasukan')->sum('amount');
        $totalPengeluaranAll = KasTransaction::where('type', 'pengeluaran')->sum('amount');
        $totalSaldoAll = $totalPemasukanAll - $totalPengeluaranAll;

        // 2.5 DATA ARSIP & REKAPITULASI MULTI-PERIODE LENGKAP (UNTUK PEMAHAMAN & RANGKUMAN AI)
        $semestersRecapSummary = "";
        foreach (AcademicService::SEMESTERS as $sem) {
            $sIn = KasTransaction::where('semester', $sem)->where('type', 'pemasukan')->sum('amount');
            $sOut = KasTransaction::where('semester', $sem)->where('type', 'pengeluaran')->sum('amount');
            $sSaldo = $sIn - $sOut;
            $sCount = KasTransaction::where('semester', $sem)->count();
            if ($sCount > 0 || $sem === $activeClassSemester) {
                $statusSurplus = $sSaldo >= 0 ? 'Surplus' : 'Defisit';
                $semestersRecapSummary .= "  - {$sem}" . ($sem === $activeClassSemester ? ' (Semester Berjalan)' : '') . ": Pemasukan Rp {$this->formatRp($sIn)}, Pengeluaran Rp {$this->formatRp($sOut)}, Saldo Rp {$this->formatRp($sSaldo)} ({$statusSurplus}, {$sCount} transaksi)\n";
            }
        }

        $yearsRecapSummary = "";
        foreach (AcademicService::TAHUN_LIST as $y) {
            $yIn = KasTransaction::whereYear('date', $y)->where('type', 'pemasukan')->sum('amount');
            $yOut = KasTransaction::whereYear('date', $y)->where('type', 'pengeluaran')->sum('amount');
            $ySaldo = $yIn - $yOut;
            $yCount = KasTransaction::whereYear('date', $y)->count();
            if ($yCount > 0 || $y === $currentYear) {
                $yearsRecapSummary .= "  - Tahun {$y}" . ($y === $currentYear ? ' (Tahun Berjalan)' : '') . ": Pemasukan Rp {$this->formatRp($yIn)}, Pengeluaran Rp {$this->formatRp($yOut)}, Arus Kas Bersih Rp {$this->formatRp($ySaldo)} ({$yCount} transaksi)\n";
            }
        }

        $monthlyThisYearSummary = "";
        for ($m = 1; $m <= 12; $m++) {
            $mIn = KasTransaction::whereYear('date', $currentYear)->whereMonth('date', $m)->where('type', 'pemasukan')->sum('amount');
            $mOut = KasTransaction::whereYear('date', $currentYear)->whereMonth('date', $m)->where('type', 'pengeluaran')->sum('amount');
            $mCount = KasTransaction::whereYear('date', $currentYear)->whereMonth('date', $m)->count();
            if ($mCount > 0 || $m === $currentMonth) {
                $mName = AcademicService::getNamaBulan($m);
                $mSelisih = $mIn - $mOut;
                $monthlyThisYearSummary .= "  - {$mName} {$currentYear}" . ($m === $currentMonth ? ' (Bulan Ini)' : '') . ": Masuk Rp {$this->formatRp($mIn)}, Keluar Rp {$this->formatRp($mOut)}, Selisih Rp {$this->formatRp($mSelisih)} ({$mCount} transaksi)\n";
            }
        }

        $duesRosterRecap = "";
        foreach ($allUsers as $u) {
            $totalWeeksAllTime = DuesPayment::where('user_id', $u->id)->where('status', 'lunas')->count();
            $totalNominalAllTime = $totalWeeksAllTime * $duesRate;
            $totalWeeksSemester = DuesPayment::where('user_id', $u->id)->where('semester', $activeClassSemester)->where('status', 'lunas')->count();
            $duesRosterRecap .= "  - {$u->name} (NPM: {$u->npm}): {$totalWeeksSemester} mgg ({$activeClassSemester}) | All-Time: {$totalWeeksAllTime} mgg lunas (Total Setor: Rp {$this->formatRp($totalNominalAllTime)})\n";
        }

        $latestTransactions = KasTransaction::with('user')->latest('date')->latest('id')->take(10)->get();
        $latestTxSummary = "";
        foreach ($latestTransactions as $tx) {
            $pihak = $tx->user ? "{$tx->user->name} ({$tx->user->npm})" : "Kas Umum";
            $tgl = Carbon::parse($tx->date)->translatedFormat('d M Y');
            $simbol = $tx->type === 'pemasukan' ? '+' : '-';
            $latestTxSummary .= "  - #{$tx->id} [{$tgl}] [{$tx->semester}] [{$tx->type}] {$simbol}Rp {$this->formatRp($tx->amount)} - {$tx->description} (Pihak: {$pihak})\n";
        }

        // 3. REKAP TUNGGAKAN IURAN KAS
        $lunasNames = [];
        $nunggakDetails = [];
        $expectedWeeks = range(1, $activeWeek);
        $totalTunggakanKelas = 0;
        $duesRate = AcademicService::getDuesRatePerWeek();

        foreach ($allUsers as $u) {
            $paidWeeks = DuesPayment::where('user_id', $u->id)
                ->where('semester', $activeClassSemester)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->where('status', 'lunas')
                ->pluck('week_number')
                ->map(fn($w) => (int) $w)
                ->toArray();

            $unpaid = array_values(array_diff($expectedWeeks, $paidWeeks));
            if (empty($unpaid)) {
                $lunasNames[] = "{$u->name} (Lunas Mg 1-{$activeWeek})";
            } else {
                $nom = count($unpaid) * $duesRate;
                $totalTunggakanKelas += $nom;
                $nunggakDetails[] = "{$u->name} (NPM: {$u->npm}) : Belum bayar Mg " . implode(',', $unpaid) . " (Tunggakan: Rp {$this->formatRp($nom)})";
            }
        }

        $nunggakCount = count($nunggakDetails);

        // 4. STATUS TUNGGAKAN USER LOGIN SAAT INI (ALERT KHUSUS)
        $personalAlert = '';
        if ($user) {
            $userPaid = DuesPayment::where('user_id', $user->id)
                ->where('semester', $activeClassSemester)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->where('status', 'lunas')
                ->pluck('week_number')
                ->map(fn($w) => (int) $w)
                ->toArray();

            $userUnpaid = array_values(array_diff($expectedWeeks, $userPaid));
            if (!empty($userUnpaid)) {
                $personalNominal = count($userUnpaid) * $duesRate;
                $personalAlert = "User {$user->name} memiliki tunggakan kas sebesar Rp {$this->formatRp($personalNominal)} untuk Minggu ke-" . implode(', ', $userUnpaid) . ". (HANYA sebutkan jika user secara spesifik bertanya tentang iuran atau tunggakan kas).";
            } else {
                $personalAlert = "Status Iuran User: Lunas s/d Minggu ke-{$activeWeek}.";
            }
        }

        // 5. DATA DIREKTORI MAHASISWA & STATUS AKADEMIK LENGKAP
        $totalMhs = $allUsers->count();
        $aktifMhs = $allUsers->where('status_akademik', 'Aktif')->count();
        $cutiMhs = $totalMhs - $aktifMhs;

        $rosterSummary = "";
        foreach ($allUsers as $u) {
            $uRoleLabel = $this->getRoleLabel($u->role);
            $uStatus = $u->status_akademik ?: 'Aktif';
            $rosterSummary .= "- {$u->name} | NPM: {$u->npm} | Role: {$uRoleLabel} | Status Akademik: {$uStatus} | Semester: {$u->semester} (TA: {$u->tahun_ajaran})\n";
        }

        $userRoleLabel = $this->getRoleLabel($user->role ?? 'mahasiswa');

        $systemPrompt = <<<PROMPT
Kamu adalah **Asisten Cerdas Terpadu Kelas Informatika UPS Tegal** yang terintegrasi penuh secara real-time dengan SELURUH data sistem web kelas (Buku Kas, Arsip & Rekapitulasi Kas, serta Direktori Mahasiswa & Status Akademik).
User yang sedang berbicara denganmu: **{$user->name}** (NPM: {$user->npm}, Peran: **{$userRoleLabel}**).

## INTEGRITAS & CAKUPAN DATA SISTEM (MUTLAK SINKRON DENGAN WEB):
Kamu memiliki akses PENUH ke SELURUH data di web ini secara real-time:
1. Data Direktori Mahasiswa & Status Akademik ({$totalMhs} orang terdaftar, {$aktifMhs} berstatus Aktif, semester berjalan {$activeClassSemester}).
2. Data Struktur Pengurus Kelas (Super Admin: {$adminName}, Ketua Kelas: {$ketuaName}, Bendahara: {$bendaharaName}).
3. Data Keuangan & Buku Kas (Saldo aktif Rp {$this->formatRp($saldoSemester)}, tarif Rp {$this->formatRp($duesRate)}/mgg, riwayat transaksi, dan rekap tunggakan).
4. Data Arsip & Rekapitulasi Kas (Multi-periode bulanan, semesteran 1 s/d 8, tahunan, dan all-time).

**LARANGAN KERAS BERDALIH TIDAK MEMILIKI DATA**:
- DILARANG KERAS menolak atau mengatakan "tidak memiliki data mengenai status akademik mahasiswa", "hanya dapat memberikan informasi terkait kas", atau dalih sejenisnya!
- Seluruh data mahasiswa, status keaktifan akademik, peran, dan keuangan sudah disediakan secara transparan dan lengkap di prompt ini. Wajib jawab semua pertanyaan pengguna mengenai data web dengan akurat, percaya diri, dan sinkron dengan sistem web!

## KEPRIBADIAN & GAYA INTERAKSI:
1. **MENJAWAB TEPAT & RELEVAN SESUAI PERTANYAAN (UTAMA)**:
   - Jawablah TEPAT dan HANYA apa yang ditanyakan pengguna. Jangan berbelit-belit atau memberikan informasi/tutorial yang tidak diminta.
   - Jika pengguna bertanya tentang data kas (saldo, riwayat transaksi, siapa yang belum bayar kas, total pemasukan/pengeluaran) ATAU data mahasiswa/akademik (status keaktifan, total mahasiswa, semester, pengurus), LANGSUNG berikan data tersebut secara ringkas, jelas, dan akurat.
   - JANGAN memberikan panduan navigasi tombol atau tutorial letak tombol jika pengguna TIDAK bertanya letak tombol atau cara navigasi.
   - Info status tunggakan pengguna login: {$personalAlert}. HANYA cantumkan informasi ini jika pengguna secara eksplisit menanyakan status iuran atau tunggakan dirinya sendiri; JANGAN menyisipkannya pada sapaan, percakapan santai, atau pertanyaan umum.

2. **BATASAN KEAMANAN, PRIVASI & ETIKA (MUTLAK DIHARGAI & DITOLAK)**:
   - **Privasi Kredensial (Private)**: DILARANG KERAS membeberkan atau menyebutkan password, kata sandi, hash (bcrypt), token, PIN, atau kredensial akun siapa pun (baik akun sendiri maupun admin/pengguna lain). Tolak tegas: "Informasi kata sandi (password), hash, dan kredensial akun bersifat rahasia dan privat demi menjaga keamanan akun dan sistem."
   - Jika pengguna bertanya cara mengganti kata sandi akunnya sendiri, jelaskan bahwa mereka dapat mengubahnya melalui tombol **"Pengaturan Sandi"** di navigasi sidebar kiri bawah.
   - **Bahasa Kasar & Ujaran Kebencian (Toxic)**: DILARANG merespons kata-kata kasar, caci maki, ejekan, atau hinaan. Tolak dengan sopan dan ingatkan untuk bertutur kata baik.
   - **Konten Vulgar & Pornografi (Vulgar)**: DILARANG KERAS melayani percakapan bermuatan seksual, vulgar, cabul, atau pornografi. Tolak dengan tegas dan sopan.
   - **Hak Akses CRUD**: Tetap patuhi otorisasi peran (Super Admin & Ketua Kelas: Akun & Semester; Bendahara: Kas & Iuran; Mahasiswa: Read-Only). Tolak aksi mutasi jika di luar hak peran.

3. **BISA NGOBROL SANTAI & RAMAH**:
   - Sambut sapaan pengguna dengan hangat, akrab, dan bersahabat (misal: "Halo {$user->name}!", "Hai! Selamat pagi/siang/malam", "Wa'alaikumsalam!", dll.).
   - Bisa diajak obrolan ringan dan interaksi kasual seperti teman kelas yang ramah (contoh: "Apa kabar?", "Lagi ngapain?", "Siapa kamu?", "Makasih ya", "Mantap", guyonan santai). Jawab dengan luwes, santai, sopan, dan manusiawi (tidak kaku seperti robot).

4. **FORMAT JAWABAN BERSIH & RAPI (MUTLAK)**:
   - DILARANG menggunakan tabel markdown (karakter pipa `|`). Format tabel membuat tampilan chat sempit dan berantakan.
   - DILARANG menggunakan tag HTML mentah seperti `<br>`, `<div>`, `<b>`, dll.
   - DILARANG menggunakan heading markdown bertingkat seperti `###`, `##`, atau garis horizontal `---` yang berlebihan.
   - Gunakan format list bullet point rapi (`- ` atau `• `) atau poin nomor (`1. `, `2. `) dan teks tebal `**kata kunci**`.

## PENGETAHUAN LENGKAP PENEMPATAN & LETAK TOMBOL PADA WEB:
Kamu memahami tata letak fisik dan letak persis seluruh tombol pada antarmuka web:
1. Sidebar Kiri (Navigasi Utama):
   • Menu Halaman (Tengah Sidebar): Dashboard (ke-1), Buku Kas Kelas (ke-2), Arsip & Rekapitulasi (ke-3), serta Kelola Akun & Semester (ke-4, khusus Super Admin & Ketua Kelas).
   • Tombol 'Pengaturan Sandi' (Ubah PW Mandiri): Terletak di bagian bawah sidebar di atas tombol Keluar (Logout). Bisa diakses mandiri oleh semua pengguna untuk mengganti sandi akun sendiri.
   • Tombol 'Keluar / Logout': Terletak di sudut paling bawah sidebar (warna merah).
2. Header Atas Layar (Top App Bar):
   • Sudut Kiri: Tombol Hamburger Menu (tiga garis horizontal) untuk membuka/tutup sidebar.
   • Sudut Kanan: Jam real-time, Badge Semester Aktif (contoh: Semester 5 (Kelas 5A)), dan Badge Role login.
3. Halaman Dashboard (Menu ke-1 Sidebar):
   • Menampilkan status iuran kas bulan berjalan (4 minggu M1-M4), riwayat kepatuhan iuran pribadi per bulan di semester aktif, serta info pengurus kelas aktif (Ketua & Bendahara Kelas).
4. Halaman Buku Kas Kelas (Menu ke-2 Sidebar):
   • Toolbar Atas: Filter Semester/Bulan/Tahun, tombol pintas Arsip Kas, serta tombol '+ Catat Transaksi' dan 'Atur Tarif Iuran' (khusus Bendahara).
   • Matriks Iuran Mingguan: Monitoring status bayar per mahasiswa untuk Minggu 1 s/d 4 beserta tombol 'Bayar' dan 'Batalkan' (khusus Bendahara).
5. Halaman Arsip & Rekapitulasi (Menu ke-3 Sidebar):
   • Pusat informasi saldo kas, total pemasukan, total pengeluaran, dan riwayat mutasi (read-only).
   • Bar Tab Mode di bagian atas: 'Per Bulan', 'Per Semester', dan 'Semua Periode'.
6. Halaman Kelola Akun & Semester (Menu ke-4 Sidebar - Khusus Super Admin & Ketua Kelas):
   • Toolbar Kanan Atas: Tombol 'Tambah Akun Baru'.
   • Panel Sinkronisasi Semester Aktif Kelas (Semester 1 s/d 8) dan kenaikan semester massal.
   • Daftar Akun Pengguna: Ubah role, reset sandi, dan hapus akun.
7. Widget AI Chatbot:
   • Tombol mengambang (floating) di sudut kanan bawah layar pada semua halaman, dan jendela chat dapat digeser (draggable) posisinya di layar.

JIKA PENGGUNA BERTANYA CARA AKSES / LETAK / POSISI / PENEMPATAN SUATU FITUR ATAU TOMBOL:
Jelaskan langkah-langkah navigasinya dan letak fisiknya secara presisi (halaman, letak di Sidebar Kiri / Header Atas / Toolbar Kanan Atas / Kolom Tabel, warna & tampilan tombol/fitur, serta peran yang berhak mengaksesnya).

## WAJIB TANYAKAN ATRIBUT DETAIL JIKA PERINTAH KURANG LENGKAP:
Jika pengguna meminta suatu aksi (mencatat, mengedit, atau menghapus transaksi kas/akun), namun atributnya belum dicantumkan atau kurang jelas:
- JANGAN langsung mengeksekusi secara sembarangan atau memberikan pesan error kaku.
- TANYAKAN SECARA DETAIL atribut-atribut apa saja yang harus dicantumkan (seperti nominal, keterangan transaksi, atau pilihan semester), dan berikan contoh perintah yang jelas.

## HAK AKSES PERAN & KEMAMPUAN CRUD LENGKAP:
AI SUDAH BISA melakukan CRUD data (Menambah, Mengedit, Menghapus) secara langsung sesuai hak akses masing-masing role:
1. Super Admin & Ketua Kelas:
   - Tambah Akun: `tambah akun [nama] npm [npm] role [role]`
   - Edit Role Akun: `ubah role [nama/npm] jadi [admin/bendahara/ketua/mahasiswa]`
   - Hapus Akun: `hapus akun [nama/npm]`
   - Ubah Semester: `ubah semester aktif ke [Semester X]`
   - Memiliki hak akses penuh manajemen akun, semester, dan direktori mahasiswa.
2. Bendahara Kelas:
   - Tambah Transaksi: `catat pemasukan [nominal] [ket]` & `catat pengeluaran [nominal] [ket]`
   - Edit Transaksi: `edit transaksi #[id] nominal [angka]` & `edit transaksi #[id] keterangan [teks]`
   - Hapus Transaksi: `hapus transaksi #[id]`
   - Lihat Backup Terhapus & Pulihkan: `lihat transaksi terhapus` & `pulihkan transaksi #[id]`
   - Atur Tarif Iuran Kas: `ubah tarif kas jadi [nominal]` / `atur iuran mingguan [nominal]`
   - Batalkan Iuran: `batalkan iuran [nama] minggu [ke-X]`
   - Dilarang kelola akun atau semester.
3. Mahasiswa:
   - Read-Only (hanya membaca data). Dilarang menambah, mengedit, atau menghapus data keuangan atau akun.
*Jika user meminta aksi di luar hak perannya, TOLAK SECARA LANGSUNG, TEGAS, DAN SINGKAT.*

## DATA SINKRONISASI REAL-TIME KELAS & BUKU KAS:
- Kelas: Informatika {$kelasLabel} ({$activeClassSemester}, TA {$tahunAjaran})
- Waktu Kalender Nyata: Bulan {$namaBulan} {$currentYear} (Bulan ke-{$currentMonth}, Tanggal {$today->day}, Minggu Aktif ke-{$activeWeek})
- Siklus Bulanan Kas: Buku Kas tersinkronisasi 100% otomatis dengan waktu nyata. Saat berganti bulan kalender, sistem langsung beralih ke bulan baru, pendataan iuran dimulai dari awal untuk bulan baru tersebut, arsip bulan lalu tetap tersimpan rapi, dan saldo kas per semester berlanjut secara kumulatif.
- Pengurus: Super Admin: {$adminName} | Ketua Kelas: {$ketuaName} | Bendahara: {$bendaharaName}

### Keuangan Kas ({$activeClassSemester}):
- Saldo Aktif: Rp {$this->formatRp($saldoSemester)} | Pemasukan: Rp {$this->formatRp($pemasukanSemester)} | Pengeluaran: Rp {$this->formatRp($pengeluaranSemester)}
- Total Saldo Semua Semester: Rp {$this->formatRp($totalSaldoAll)}
- Iuran Kas: Rp {$this->formatRp($duesRate)}/minggu
- Rekap Tunggakan Kas Bulan Ini ({$nunggakCount} orang menunggak, total Rp {$this->formatRp($totalTunggakanKelas)}):
  * Yang Menunggak:
  {$this->formatNunggakList($nunggakDetails)}
  * Yang Lunas: {$this->formatLunasList($lunasNames)}

## GENERATOR PESAN PENGINGAT WHATSAPP (BROADCAST TAGIHAN KAS):
Jika pengguna meminta pesan pengingat tagihan kas untuk grup WA, broadcast WhatsApp, atau format pengingat iuran:
- Susun pesan siap salin dengan format teks tebal WhatsApp (*teks*), emoji rapi, dan instruksi jelas.
- ATURAN MUTLAK & SANGAT KETAT: HANYA CANTUMKAN MAHASISWA YANG MENUNGGAK (BELUM LUNAS). Mahasiswa yang sudah lunas DILARANG dicantumkan dalam daftar tagihan/pengingat kas!
- Rincikan daftar mahasiswa menunggak (nama, NPM, minggu tunggakan, dan nominal per orang), total tunggakan kelas, serta instruksi setor ke Bendahara Kelas ({$bendaharaName}).
- Jika seluruh mahasiswa sudah lunas, beritahukan dengan ucapan terima kasih dan apresiasi bahwa tidak ada tunggakan kas.

## DATA LENGKAP ARSIP & REKAPITULASI KAS (PERIODE BULAN, SEMESTER, TAHUN, ALL-TIME):
Kamu memiliki akses PENUH ke seluruh data arsip kas kelas untuk menjawab pertanyaan, menganalisis, dan merangkum segala aspek keuangan kelas:
1. Rekapitulasi Kas Per Semester (Semester 1 s/d 8):
{$semestersRecapSummary}
2. Rekapitulasi Kas Per Tahun:
{$yearsRecapSummary}
3. Rincian Mutasi Bulanan Tahun Berjalan ({$currentYear}):
{$monthlyThisYearSummary}
4. Akumulasi Iuran Mahasiswa (Semester Aktif & All-Time):
{$duesRosterRecap}
5. 10 Catatan Transaksi Kas Terkini:
{$latestTxSummary}

### KETENTUAN INTEGRITAS ARSIP:
- Data pada Menu Arsip bersifat dokumen audit otentik (read-only) untuk menjamin transparansi, kejujuran, dan akuntabilitas pembukuan kelas.
- Data Arsip TIDAK BISA ditambah, diubah, atau dihapus secara langsung.
- Data arsip secara dinamis dan otomatis bersumber dari catatan transaksi riil di Buku Kas. Jika data transaksi sumber diubah atau dihapus oleh Bendahara Kelas yang berhak (dengan penginputan data yang jelas, lengkap, dan terkonfirmasi), maka data arsip akan otomatis terupdate atau terhapus menyesuaikan data asli secara real-time.
- AI BISA melihat, membaca, menganalisis, dan merangkum seluruh data arsip untuk menjawab pertanyaan pengguna secara komprehensif.
- AI DILARANG keras menambah, mengubah, atau menghapus arsip itu sendiri secara langsung.

### DIREKTORI MAHASISWA & STATUS AKADEMIK LENGKAP ({$totalMhs} Orang):
• Total Mahasiswa Terdaftar: {$totalMhs} Orang
• Status Mahasiswa Aktif: {$aktifMhs} Orang (100% aktif di {$activeClassSemester})
• Status Mahasiswa Cuti / Non-Aktif: {$cutiMhs} Orang
• Rincian Mahasiswa, Role & Status Akademik:
{$rosterSummary}

PANDUAN MENJAWAB PERTANYAAN DIREKTORI & STATUS AKADEMIK:
Jika pengguna bertanya mengenai status akademik mahasiswa, total mahasiswa, nama mahasiswa, atau struktur pengurus, jawablah secara lugas, rinci, dan percaya diri menggunakan data riil di atas. DILARANG KERAS menolak atau beralasan tidak memiliki data!

## PRIVASI:
DILARANG memberikan password, hash, token, atau kredensial akun kepada siapa pun. Tolak dengan: "Informasi kredensial login bersifat privat dan rahasia demi keamanan sistem."
PROMPT;

        return $systemPrompt;
    }

    private function getRoleLabel($role)
    {
        return [
            'admin' => 'Super Admin',
            'bendahara' => 'Bendahara Kelas',
            'ketua' => 'Ketua Kelas',
            'mahasiswa' => 'Mahasiswa',
        ][$role] ?? 'Mahasiswa';
    }

    private function formatNunggakList($list)
    {
        if (empty($list)) {
            return "    Semua mahasiswa sudah lunas!";
        }
        return "    - " . implode("\n    - ", $list);
    }

    private function formatLunasList($list)
    {
        if (empty($list)) {
            return "Belum ada yang lunas";
        }
        return implode(', ', $list);
    }

    private function formatRp($amount)
    {
        return number_format((float) $amount, 0, ',', '.');
    }

    /**
     * Menghasilkan teks template broadcast WhatsApp siap salin untuk pengingat tagihan kas.
     * Sesuai ketentuan: HANYA mencantumkan daftar mahasiswa yang memiliki tunggakan (belum lunas).
     */
    private function generateWaBroadcastNunggak($currentUser = null)
    {
        $activeClassSemester = AcademicService::getActiveSemester();
        $duesRate = AcademicService::getDuesRatePerWeek();
        $today = Carbon::now();
        $currentMonth = (int) $today->month;
        $currentYear = (int) $today->year;
        $activeWeek = min(4, (int) ceil($today->day / 7));
        $expectedWeeks = range(1, $activeWeek);
        $namaBulan = AcademicService::getNamaBulan($currentMonth);

        $bendahara = User::where('role', 'bendahara')->first();
        $bendaharaName = $bendahara ? "{$bendahara->name} (NPM: {$bendahara->npm})" : "Bendahara Kelas";

        $users = User::whereIn('role', ['mahasiswa', 'admin', 'ketua', 'bendahara'])
            ->where(function($q) {
                $q->whereNull('status_akademik')
                  ->orWhere('status_akademik', 'Aktif')
                  ->orWhere('status_akademik', 'like', '%aktif%');
            })
            ->orderBy('name', 'asc')
            ->get();

        $unpaidList = [];
        $totalTunggakanNominal = 0;

        foreach ($users as $u) {
            $paidWeeks = DuesPayment::where('user_id', $u->id)
                ->where('semester', $activeClassSemester)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->where('status', 'lunas')
                ->pluck('week_number')
                ->map(fn($w) => (int) $w)
                ->toArray();

            $unpaid = array_values(array_diff($expectedWeeks, $paidWeeks));
            if (!empty($unpaid)) {
                $countUnpaid = count($unpaid);
                $nom = $countUnpaid * $duesRate;
                $totalTunggakanNominal += $nom;
                $unpaidList[] = [
                    'name' => $u->name,
                    'npm' => $u->npm,
                    'unpaid_weeks' => $unpaid,
                    'count' => $countUnpaid,
                    'nominal' => $nom,
                ];
            }
        }

        if (empty($unpaidList)) {
            return "✅ *Alhamdulillah! Seluruh mahasiswa telah lunas iuran kas.*\n\n"
                . "📢 *INFORMASI IURAN KAS KELAS INFORMATIKA*\n"
                . "📅 *Periode:* {$namaBulan} {$currentYear} (s/d Minggu ke-{$activeWeek})\n"
                . "🎓 *Semester:* {$activeClassSemester}\n\n"
                . "Seluruh mahasiswa telah melunasi iuran kas sampai dengan minggu ini. Tidak ada mahasiswa yang memiliki tunggakan kas saat ini. Terima kasih atas kedisiplinan dan kerja samanya! 🙏✨";
        }

        $totalNunggak = count($unpaidList);
        $totalNominalStr = $this->formatRp($totalTunggakanNominal);
        $duesRateStr = $this->formatRp($duesRate);

        $msg = "📢 *PENGINGAT IURAN KAS KELAS INFORMATIKA*\n"
             . "📅 *Periode:* {$namaBulan} {$currentYear} (s/d Minggu ke-{$activeWeek})\n"
             . "🎓 *Semester:* {$activeClassSemester}\n"
             . "💰 *Tarif Iuran:* Rp {$duesRateStr} / minggu\n\n"
             . "Halo teman-teman sekalian! Mohon perhatiannya untuk rekap tagihan iuran kas kelas.\n"
             . "Berikut daftar teman-teman yang *belum melunasi* iuran kas:\n\n"
             . "*Daftar Mahasiswa Menunggak:*\n";

        $no = 1;
        foreach ($unpaidList as $item) {
            $weeksStr = implode(', ', $item['unpaid_weeks']);
            $nomStr = $this->formatRp($item['nominal']);
            $msg .= "{$no}. *{$item['name']}* (NPM: {$item['npm']})\n"
                  . "   • Tunggakan: {$item['count']} Minggu (Mg {$weeksStr})\n"
                  . "   • Total Tagihan: *Rp {$nomStr}*\n";
            $no++;
        }

        $msg .= "\n📊 *Ringkasan Tunggakan:*\n"
              . "• Jumlah Mahasiswa: *{$totalNunggak} orang*\n"
              . "• Total Belum Terkumpul: *Rp {$totalNominalStr}*\n\n"
              . "📌 *Instruksi Pembayaran:*\n"
              . "Bagi teman-teman yang namanya tercantum di atas, mohon kesediaannya untuk segera melunasi iuran kas melalui Bendahara Kelas:\n"
              . "👤 *Bendahara:* {$bendaharaName}\n"
              . "*(Pembayaran dapat dilakukan secara tunai langsung atau konfirmasi transfer)*\n\n"
              . "Terima kasih atas pengertian dan kerja sama teman-teman demi kelancaran kegiatan kelas kita bersama! 🙏✨";

        return $msg;
    }

    /**
     * Parsing nominal rupiah fleksibel dari teks pengguna (contoh: 500000, 1.5jt, 250rb, 2,5 juta)
     */
    private function parseNominalFromText(string $text): ?float
    {
        $clean = strtolower($text);

        // 1. Format juta: "1.5 jt", "2.5 juta", "2jt", "3 juta"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:jt|juta)\b/i', $clean, $m)) {
            $val = (float) str_replace(',', '.', $m[1]);
            return $val * 1000000;
        }

        // 2. Format ribu / k: "500rb", "150k", "250 rb", "750 ribu"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:rb|k|ribu)\b/i', $clean, $m)) {
            $val = (float) str_replace(',', '.', $m[1]);
            return $val * 1000;
        }

        // 3. Format angka dengan pemisah ribuan titik: "1.500.000", "750.000", "Rp 50.000"
        if (preg_match('/(?:rp\.?\s*)?(\d{1,3}(?:\.\d{3})+(?:,\d+)?)/i', $clean, $m)) {
            return (float) str_replace(['.', ','], ['', '.'], $m[1]);
        }

        // 4. Format angka polos 4-10 digit (contoh: 500000, 150000)
        if (preg_match('/\b(\d{4,10})\b/', $clean, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    /**
     * Fitur Inovasi 1: Rekap Kas dalam Excel (.csv) On-Demand
     */
    private function resolveRekapExcel(string $msg): ?string
    {
        $isExcelQuery = (
            (preg_match('/\b(?:rekap|laporan|buku\s*kas|data)\b/i', $msg) && preg_match('/\b(?:excel|csv|spreadsheet)\b/i', $msg)) ||
            preg_match('/\b(?:ekspor|export|download|unduh)\s+(?:kas\s+)?(?:ke\s+|dalam\s+)?(?:excel|csv)\b/i', $msg) ||
            preg_match('/\b(?:bikin|buat|buatkan|generate)\s+(?:rekap|laporan)?\s*(?:kas\s*)?(?:dalam|ke)?\s*excel\b/i', $msg) ||
            preg_match('/rekap\s*kas\s*excel/i', $msg)
        );

        if (!$isExcelQuery) {
            return null;
        }

        // Tentukan semester target (default: semester aktif)
        $targetSemester = AcademicService::getActiveSemester();
        foreach (AcademicService::SEMESTERS as $sem) {
            if (stripos($msg, $sem) !== false || preg_match('/\bsemester\s*' . AcademicService::parseSemesterNumber($sem) . '\b/i', $msg)) {
                $targetSemester = $sem;
                break;
            }
        }

        $txCount = KasTransaction::where('semester', $targetSemester)->count();
        $totalIn = (float) KasTransaction::where('semester', $targetSemester)->where('type', 'pemasukan')->sum('amount');
        $totalOut = (float) KasTransaction::where('semester', $targetSemester)->where('type', 'pengeluaran')->sum('amount');
        $saldo = $totalIn - $totalOut;

        $totalStudents = User::whereIn('role', ['mahasiswa', 'bendahara', 'ketua', 'admin'])->count();
        $duesRate = AcademicService::getDuesRatePerWeek();
        $kelasLabel = AcademicService::getKelasLabel($targetSemester);
        $tahunAjaran = AcademicService::getTahunAjaran($targetSemester);

        // Catat jejak audit untuk permintaan rekap
        AuditLog::record(
            'minta_rekap_excel',
            "Meminta berkas rekap kas Excel (.csv) periode {$targetSemester} via AI Chatbot",
            auth()->user(),
            'chatbot',
            ['semester' => $targetSemester, 'transactions' => $txCount]
        );

        $downloadUrl = route('kas.exportExcel', ['semester' => $targetSemester]);

        return "📥 **Rekap Kas Kelas Format Excel (.csv) Siap Diunduh**:\n\n"
            . "Sistem telah menyusun rekapitulasi pembukuan kas Program Studi S1 Informatika UPS Tegal untuk **{$targetSemester}** ({$kelasLabel}, TA {$tahunAjaran}):\n\n"
            . "📊 **Ringkasan Finansial Periode Ini**:\n"
            . "• Total Transaksi Mutasi: **{$txCount} Catatan**\n"
            . "• Pemasukan: **Rp " . $this->formatRp($totalIn) . "**\n"
            . "• Pengeluaran: **Rp " . $this->formatRp($totalOut) . "**\n"
            . "• Saldo Kas Berjalan: **Rp " . $this->formatRp($saldo) . "**\n"
            . "• Tarif Iuran Mingguan: **Rp " . $this->formatRp($duesRate) . " / minggu**\n\n"
            . "📁 **Struktur Lembar Kerja Excel yang Dihasilkan**:\n"
            . "1. **Bagian 1: Buku Kas Mutasi Rinci**: Nomor urut, tanggal transaksi, kode semester, debit, kredit, saldo berjalan per transaksi, nama mahasiswa penyetor, dan keterangan mutasi.\n"
            . "2. **Bagian 2: Rekapitulasi Iuran Mahasiswa**: Daftar seluruh {$totalStudents} mahasiswa kelas beserta rekapitulasi jumlah minggu yang telah lunas dan total setoran kas.\n"
            . "3. **Enkoding UTF-8 BOM**: Berkas kompatibel langsung dibuka pada Microsoft Excel, Google Sheets, maupun LibreOffice tanpa masalah karakter.\n\n"
            . "👉 **Unduh Berkas Sekarang**:\n"
            . "[📥 Unduh Rekap Kas Excel (.csv) {$targetSemester}]({$downloadUrl})";
    }

    /**
     * Fitur Inovasi 2: Jejak Audit (Audit Trail)
     */
    private function resolveJejakAudit(string $msg): ?string
    {
        $isAuditQuery = (
            preg_match('/\b(?:jejak\s*audit|audit\s*log|log\s*aktivitas|riwayat\s*aktivitas|catatan\s*audit|riwayat\s*audit)\b/i', $msg) ||
            preg_match('/\bsiapa\s+(?:yang\s+)?(?:terakhir\s+)?(?:mengubah|merubah|edit|menghapus|mencatat)\s*(?:data|kas|sistem|pembukuan)?\b/i', $msg)
        );

        if (!$isAuditQuery) {
            return null;
        }

        $logs = AuditLog::with('user')->latest()->take(6)->get();

        if ($logs->isEmpty()) {
            return "🔍 **Jejak Audit Sistem Keuangan Kelas (Audit Trail)**:\n\n"
                . "🛡️ **Status Sistem Audit: AKTIF**\n"
                . "Belum ada catatan aktivitas baru yang tersimpan dalam log audit. Setiap mutasi kas, pembayaran iuran, pembaruan status, maupun penggantian hak akses peran akan otomatis dicatat secara permanen dan real-time demi menjaga integritas pembukuan kelas.";
        }

        $reply = "🔍 **Jejak Audit Aktivitas & Mutasi Sistem Keuangan Kas Kelas**:\n\n"
            . "Sistem mencatat setiap perubahan data secara real-time dan permanen untuk menjamin transparansi serta akuntabilitas bersama:\n\n";

        $actionLabels = [
            'catat_transaksi' => 'Pencatatan Transaksi',
            'edit_transaksi' => 'Perubahan Transaksi',
            'hapus_transaksi' => 'Penghapusan Transaksi',
            'pulihkan_transaksi' => 'Pemulihan Transaksi Kas (Backup)',
            'bayar_iuran' => 'Pembayaran Iuran Kas',
            'batal_iuran' => 'Pembatalan Iuran Kas',
            'atur_tarif_iuran' => 'Penyesuaian Tarif Kas',
            'tambah_akun' => 'Pendaftaran Akun Baru',
            'ubah_role' => 'Perubahan Hak Akses Role',
            'ubah_akademik' => 'Pembaruan Data Akademik',
            'ubah_semester_aktif' => 'Pergantian Semester Aktif',
            'kenaikan_semester' => 'Kenaikan Semester Massal',
            'reset_password' => 'Reset Kata Sandi Akun',
            'hapus_akun' => 'Penghapusan Akun',
            'ekspor_excel' => 'Ekspor Laporan Kas Excel',
            'minta_rekap_excel' => 'Permintaan Rekap Excel AI',
            'ekspor_mahasiswa_excel' => 'Ekspor Direktori Mahasiswa',
        ];

        $no = 1;
        foreach ($logs as $log) {
            $timeStr = $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-';
            $actorName = $log->user_name ?: ($log->user ? $log->user->name : 'Sistem');
            $roleLabel = $this->getRoleLabel($log->user_role ?: ($log->user ? $log->user->role : 'system'));
            $npmStr = $log->user_npm ? " | NPM: {$log->user_npm}" : "";
            $actionStr = $actionLabels[$log->action] ?? ucwords(str_replace('_', ' ', $log->action));
            $channelStr = $log->source === 'chatbot' ? '🤖 AI Chatbot' : '💻 Formulir Web';

            $reply .= "{$no}. ⏱️ **{$timeStr} WIB** — **{$actorName}** ({$roleLabel}{$npmStr})\n"
                    . "   • Aksi: **{$actionStr}** ({$channelStr})\n"
                    . "   • Keterangan: {$log->description}\n";
            $no++;
        }

        $reply .= "\n🛡️ **Ketentuan Integritas & Keamanan**:\n"
            . "• Setiap mutasi keuangan dan wewenang pengguna diverifikasi secara atomik.\n"
            . "• Jejak audit tersimpan permanen di database server dan bersifat *immutable* (tidak dapat dimanipulasi) guna menjaga kepercayaan seluruh mahasiswa.";

        return $reply;
    }

    /**
     * Fitur Inovasi 3: Simulasi Anggaran & Proyeksi Saldo (Budget Simulation & Cashflow Forecasting)
     */
    private function resolveSimulasiAnggaran(string $msg): ?string
    {
        $isSimulasiQuery = (
            preg_match('/\b(?:simulasi\s*anggaran|proyeksi\s*(?:kas|saldo)|estimasi\s*anggaran|hitung\s*anggaran)\b/i', $msg) ||
            preg_match('/\b(?:apakah|cukupkah)\s*(?:uang\s*)?kas\s*(?:cukup|bisa|mencukupi)\b/i', $msg) ||
            (preg_match('/\b(?:simulasi|proyeksi|estimasi)\b/i', $msg) && preg_match('/\b(?:biaya|dana|anggaran|acara|kegiatan|saldo)\b/i', $msg))
        );

        if (!$isSimulasiQuery) {
            return null;
        }

        $activeClassSemester = AcademicService::getActiveSemester();
        $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
        $tahunAjaran = AcademicService::getTahunAjaran($activeClassSemester);

        $totalIn = (float) KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
        $totalOut = (float) KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
        $currentBalance = $totalIn - $totalOut;

        $activeStudents = User::whereIn('role', ['mahasiswa', 'bendahara', 'ketua', 'admin'])
            ->where(function($q) {
                $q->whereNull('status_akademik')
                  ->orWhere('status_akademik', 'Aktif')
                  ->orWhere('status_akademik', 'like', '%aktif%');
            })->count();

        if ($activeStudents === 0) {
            $activeStudents = max(1, User::count());
        }

        $duesRate = AcademicService::getDuesRatePerWeek();
        $weeklyPotential = $activeStudents * $duesRate;
        $monthlyPotential = $weeklyPotential * 4;

        $budget = $this->parseNominalFromText($msg);

        // Kasus 1: Pengguna tidak menyebutkan nominal -> Tampilkan ringkasan kapasitas & 3 skenario simulasi
        if ($budget === null) {
            $s1 = 150000;  // Rutin / ATK / Takjil
            $s2 = 600000;  // Seminar / Workshop
            $s3 = 1500000; // Makrab / Acara Akbar

            $s1Diff = $currentBalance - $s1;
            $s2Diff = $currentBalance - $s2;
            $s3Diff = $currentBalance - $s3;

            $s1Text = $s1Diff >= 0 ? "✅ **Cukup** (Sisa Kas: Rp " . $this->formatRp($s1Diff) . ")" : "❌ **Defisit** (Kurang: Rp " . $this->formatRp(abs($s1Diff)) . ")";
            $s2Text = $s2Diff >= 0 ? "✅ **Cukup** (Sisa Kas: Rp " . $this->formatRp($s2Diff) . ")" : "❌ **Defisit** (Kurang: Rp " . $this->formatRp(abs($s2Diff)) . ")";
            $s3Text = $s3Diff >= 0 ? "✅ **Cukup** (Sisa Kas: Rp " . $this->formatRp($s3Diff) . ")" : "❌ **Defisit** (Kurang: Rp " . $this->formatRp(abs($s3Diff)) . ")";

            return "💡 **Kalkulator & Simulasi Anggaran Kegiatan Kas Kelas**:\n\n"
                . "📌 **Kapasitas Finansial Kelas Saat Ini ({$activeClassSemester} - {$kelasLabel})**:\n"
                . "• Saldo Kas Aktif: **Rp " . $this->formatRp($currentBalance) . "**\n"
                . "• Jumlah Mahasiswa Aktif: **{$activeStudents} Orang**\n"
                . "• Tarif Iuran Kas: **Rp " . $this->formatRp($duesRate) . " / minggu**\n"
                . "• Potensi Pemasukan Iuran: **Rp " . $this->formatRp($weeklyPotential) . " / minggu** (Rp " . $this->formatRp($monthlyPotential) . " / bulan)\n\n"
                . "🎯 **Simulasi 3 Tingkat Skenario Anggaran Acara**:\n"
                . "1. **Skenario Ringan (ATK / Konsumsi Rapat)** — Rp " . $this->formatRp($s1) . "\n"
                . "   • Kelayakan: {$s1Text}\n"
                . "2. **Skenario Menengah (Workshop / Seminar Kelas)** — Rp " . $this->formatRp($s2) . "\n"
                . "   • Kelayakan: {$s2Text}\n"
                . "3. **Skenario Besar (Makrab / Buka Bersama Akbar)** — Rp " . $this->formatRp($s3) . "\n"
                . "   • Kelayakan: {$s3Text}\n\n"
                . "💬 **Uji Anggaran Khusus Anda Sendiri**:\n"
                . "Ketik nominal acara yang direncanakan, contoh:\n"
                . "• `simulasi anggaran 450000 untuk beli banner dan konsumsi`\n"
                . "• `apakah uang kas cukup untuk acara 800000`\n"
                . "• `simulasi anggaran 1.5jt`";
        }

        // Kasus 2: Pengguna menyebut nominal spesifik
        $diff = $currentBalance - $budget;
        $budgetStr = $this->formatRp($budget);
        $balanceStr = $this->formatRp($currentBalance);

        if ($diff >= 0) {
            // SURPLUS
            $sisaStr = $this->formatRp($diff);
            $reserveRatio = $currentBalance > 0 ? round(($diff / $currentBalance) * 100) : 0;
            $weeksToRecover = $weeklyPotential > 0 ? ceil($budget / $weeklyPotential) : 0;

            if ($reserveRatio >= 40) {
                $riskBadge = "🟢 **Tingkat Risiko: SANGAT RENDAH (Aman)**";
                $riskDesc = "Saldo kas setelah acara masih memiliki dana cadangan yang sangat kuat untuk kebutuhan tak terduga.";
            } elseif ($reserveRatio >= 15) {
                $riskBadge = "🟡 **Tingkat Risiko: SEDANG (Perlu Perhatian)**";
                $riskDesc = "Saldo mencukupi, namun cadangan kas kelas akan menipis. Pastikan iuran mingguan tetap berjalan disiplin.";
            } else {
                $riskBadge = "🟠 **Tingkat Risiko: KETAT (Waspada)**";
                $riskDesc = "Saldo kas akan hampir habis terpakai. Disarankan membatasi pengeluaran lain hingga kas kembali terkumpul.";
            }

            return "💡 **Hasil Simulasi Anggaran: DANA KAS MENCUKUPI (SURPLUS)** ✅\n\n"
                . "• Rencana Anggaran: **Rp {$budgetStr}**\n"
                . "• Saldo Kas Aktif Saat Ini: **Rp {$balanceStr}**\n"
                . "• Proyeksi Sisa Saldo Kas: **Rp {$sisaStr}** (Cadangan tersisa {$reserveRatio}%)\n"
                . "• {$riskBadge}\n"
                . "  {$riskDesc}\n\n"
                . "📈 **Proyeksi Pemulihan Arus Kas (Recovery Time)**:\n"
                . "• Berdasarkan tarif iuran Rp " . $this->formatRp($duesRate) . "/minggu dari {$activeStudents} mahasiswa (Rp " . $this->formatRp($weeklyPotential) . "/minggu):\n"
                . "• Dana yang terpakai diproyeksikan pulih kembali dalam waktu **sekitar {$weeksToRecover} minggu** setoran kas kelas normal.\n\n"
                . "✨ *Kesimpulan: Anggaran Rp {$budgetStr} layak untuk disetujui tanpa perlu memungut iuran tambahan dari mahasiswa.*";
        } else {
            // DEFISIT
            $deficit = abs($diff);
            $deficitStr = $this->formatRp($deficit);
            $perStudent = ceil($deficit / max(1, $activeStudents));
            $perStudentStr = $this->formatRp($perStudent);
            $weeksNeeded = $weeklyPotential > 0 ? ceil($deficit / $weeklyPotential) : 0;

            return "⚠️ **Hasil Simulasi Anggaran: DANA KAS BELUM MENCUKUPI (DEFISIT)**\n\n"
                . "• Rencana Anggaran: **Rp {$budgetStr}**\n"
                . "• Saldo Kas Aktif Saat Ini: **Rp {$balanceStr}**\n"
                . "• Kekurangan Dana (Defisit): **Rp {$deficitStr}**\n\n"
                . "💡 **Rekomendasi Solusi Pendanaan dari AI**:\n"
                . "1. **Iuran Tambahan Mandiri (Patungan Mahasiswa)**:\n"
                . "   Jika acara segera dilaksanakan, cukup memungut iuran tambahan sebesar:\n"
                . "   👉 **Rp {$perStudentStr} / mahasiswa** (dibagi rata untuk {$activeStudents} mahasiswa aktif).\n\n"
                . "2. **Menunggu Akumulasi Iuran Reguler**:\n"
                . "   Dengan potensi iuran mingguan Rp " . $this->formatRp($weeklyPotential) . "/minggu, defisit ini dapat tertutupi dalam waktu:\n"
                . "   👉 **Sekitar {$weeksNeeded} minggu** pengumpulan iuran reguler tanpa memungut uang tambahan.\n\n"
                . "3. **Optimalisasi Penagihan Tunggakan**:\n"
                . "   Ketik `rekap nunggak` untuk menagih mahasiswa yang belum melunasi kas guna menutup defisit ini.";
        }
    }

    /**
     * Menjelaskan letak/penempatan tombol dan cara akses fitur secara presisi sesuai antarmuka web
     */
    private function resolveButtonPlacement(string $msg, $user = null): ?string
    {
        // 0. Pengujian / Simulasi Cepat Notifikasi Ramah Token AI Habis
        if (preg_match('/\b(?:tes|test|simulasi|uji|cek)\s+(?:token\s+habis|kuota\s+habis|limit\s+token)\b/i', $msg) ||
            preg_match('/\b(?:token\s+habis|kuota\s+habis)\s+(?:tes|test|simulasi)\b/i', $msg)) {
            return $this->generateFriendlyTokenExhaustedNotice($user, $msg);
        }

        // Fitur Inovasi 1: Rekap Kas Format Excel (.csv) On-Demand
        $excelResult = $this->resolveRekapExcel($msg);
        if ($excelResult !== null) {
            return $excelResult;
        }

        // Fitur Inovasi 2: Jejak Audit (Audit Trail)
        $auditResult = $this->resolveJejakAudit($msg);
        if ($auditResult !== null) {
            return $auditResult;
        }

        // Fitur Inovasi 3: Simulasi Anggaran & Proyeksi Saldo
        $simulasiResult = $this->resolveSimulasiAnggaran($msg);
        if ($simulasiResult !== null) {
            return $simulasiResult;
        }

        // A. Format Perintah Chat Catat Transaksi Kas
        if ((preg_match('/\b(?:format|perintah|cara)\b/i', $msg) && preg_match('/\b(?:catat|mencatat|input|menginput|tambah|menambah)\b/i', $msg) && preg_match('/\b(?:pemasukan|pengeluaran|transaksi|kas)\b/i', $msg)) ||
            preg_match('/format\s+catat\s+kas/i', $msg)) {
            return "📌 **Format Perintah Chat Catat Transaksi Kas (Khusus Bendahara Kelas)**:\n\n"
                . "Bendahara Kelas dapat langsung mencatat mutasi kas melalui chatbot tanpa perlu membuka formulir:\n"
                . "• **Catat Pemasukan**:\n"
                . "  `catat pemasukan [nominal] [keterangan]`\n"
                . "  *Contoh:* `catat pemasukan 500000 Sisa dana bazar kelas`\n\n"
                . "• **Catat Pengeluaran**:\n"
                . "  `catat pengeluaran [nominal] [keterangan]`\n"
                . "  *Contoh:* `catat pengeluaran 150000 Beli spidol dan kertas HVS`\n\n"
                . "• **Edit Transaksi**:\n"
                . "  `edit transaksi #[id] nominal [angka]`\n"
                . "  `edit transaksi #[id] keterangan [teks baru]`\n\n"
                . "• **Hapus Transaksi**:\n"
                . "  `hapus transaksi #[id]`\n\n"
                . "*(Setiap instruksi mutasi kas akan meminta konfirmasi \"Ya\" sebelum dieksekusi)*";
        }

        // B. Format Perintah Chat Tambah Akun Baru
        if ((preg_match('/\b(?:format|perintah|cara)\b/i', $msg) && preg_match('/\b(?:tambah|menambah|menambahkan|buat|membuat|daftar|daftarkan|mendaftarkan)\b/i', $msg) && preg_match('/\b(?:akun|user|pengguna)\b/i', $msg)) ||
            preg_match('/format\s+tambah\s+akun/i', $msg) ||
            (preg_match('/tambah\s+akun/i', $msg) && preg_match('/\b(?:bagaimana|gimana|cara|format)\b/i', $msg))) {
            return "📌 **Format Perintah Chat Tambah Akun (Khusus Super Admin & Ketua Kelas)**:\n\n"
                . "Super Admin dan Ketua Kelas dapat mendaftarkan akun baru langsung melalui chat:\n"
                . "• **Format Perintah**:\n"
                . "  `tambah akun [Nama Lengkap] npm [10 Digit NPM] role [mahasiswa/bendahara/ketua/admin]`\n\n"
                . "• **Contoh Penggunaan**:\n"
                . "  `tambah akun Ahmad Fauzi npm 6624600099 role mahasiswa`\n\n"
                . "• **Ketentuan**:\n"
                . "  - NPM wajib 10 digit angka unik.\n"
                . "  - Kata sandi awal otomatis sama dengan NPM mahasiswa (dapat diubah mandiri di menu Pengaturan Sandi).\n"
                . "  - Pilihan role: `mahasiswa`, `bendahara`, `ketua`, atau `admin`.";
        }

        // C. Format Perintah Chat Ubah Role
        if ((preg_match('/\b(?:format|perintah|cara)\b/i', $msg) && preg_match('/\b(?:ubah|mengubah|ganti|mengganti)\b/i', $msg) && preg_match('/\brole\b/i', $msg)) ||
            preg_match('/format\s+ubah\s+role/i', $msg) ||
            (preg_match('/ubah\s+role/i', $msg) && preg_match('/\b(?:bagaimana|gimana|cara|format)\b/i', $msg))) {
            return "📌 **Format Perintah Chat Ubah Role (Khusus Super Admin & Ketua Kelas)**:\n\n"
                . "Super Admin dan Ketua Kelas dapat memperbarui hak akses pengguna langsung melalui chat:\n"
                . "• **Format Perintah**:\n"
                . "  `ubah role [Nama atau NPM] jadi [admin/bendahara/ketua/mahasiswa]`\n\n"
                . "• **Contoh Penggunaan**:\n"
                . "  `ubah role Ahmad Fauzi jadi bendahara`\n"
                . "  `ubah role 6624600099 jadi mahasiswa`\n\n"
                . "*(Perubahan role langsung tersinkronisasi ke seluruh sistem secara real-time)*";
        }

        // D. Fungsi & Integritas Halaman Arsip
        if (preg_match('/\b(?:fungsi|ketentuan|penjelasan|integritas)\s+(?:dan\s+ketentuan\s+)?(?:data\s+)?(?:pada\s+)?(?:halaman\s+)?(?:arsip|rekapitulasi)\b/i', $msg) ||
            preg_match('/\bintegritas\s+arsip\b/i', $msg)) {
            return "📁 **Fungsi & Ketentuan Halaman Arsip & Rekapitulasi Kas**:\n\n"
                . "Halaman Arsip adalah dokumen audit resmi pembukuan kas kelas:\n"
                . "• **Dokumen Audit Murni (Read-Only)**: Data arsip tidak dapat diubah atau dihapus langsung demi menjaga transparansi dan integritas laporan kas.\n"
                . "• **Tersinkronisasi Otomatis**: Jika transaksi di Buku Kas diubah atau dibatalkan oleh Bendahara secara sah, maka rekap arsip akan otomatis terupdate secara real-time.\n"
                . "• **3 Sudut Pandang Rekapitulasi**:\n"
                . "  1. *Per Bulan*: Mutasi pemasukan, pengeluaran, dan selisih kas tiap bulan.\n"
                . "  2. *Per Semester*: Laporan akumulasi Semester 1 s/d 8.\n"
                . "  3. *Semua Periode*: Total akumulasi setoran iuran kas tiap mahasiswa sepanjang masa kuliah (All-Time).\n\n"
                . "• **Bantuan AI**: Anda dapat meminta AI untuk menganalisis, merangkum, dan membandingkan arus kas antar semester kapan saja!";
        }

        // E. Kontak / Bendahara Kelas
        if (preg_match('/\b(?:siapa|kontak|nama)\s+(?:bendahara|bendahara\s+kelas)\b/i', $msg)) {
            $bendahara = User::where('role', 'bendahara')->first();
            $bName = $bendahara ? "{$bendahara->name} (NPM: {$bendahara->npm})" : "Belum ditentukan";
            return "👤 **Bendahara Kelas Informatika**:\n\n"
                . "• **Nama Bendahara**: {$bName}\n"
                . "• **Tugas & Wewenang**: Mengelola pembukuan kas kelas, mencatat transaksi masuk/keluar, mengatur tarif mingguan, serta memverifikasi setoran iuran kas.\n"
                . "• **Metode Pembayaran**: Pembayaran dapat diserahkan langsung secara tunai atau konfirmasi transfer ke Bendahara.";
        }

        // F. Status Akademik Mahasiswa
        if (preg_match('/\b(?:status\s+akademik|keaktifan\s+mahasiswa|status\s+keaktifan|keaktifan\s+akademik)\b/i', $msg) ||
            (preg_match('/\bstatus\b/i', $msg) && preg_match('/\bakademik\b/i', $msg))) {
            $activeClassSemester = AcademicService::getActiveSemester();
            $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
            $tahunAjaran = AcademicService::getTahunAjaran($activeClassSemester);
            $users = User::orderBy('npm', 'asc')->get();
            $total = $users->count();
            $aktif = $users->where('status_akademik', 'Aktif')->count();
            $nonAktif = $total - $aktif;

            $reply = "🎓 **Status Akademik Mahasiswa Kelas Informatika**:\n\n"
                   . "• **Semester Aktif Kelas**: {$activeClassSemester} (Kelas {$kelasLabel}, TA {$tahunAjaran})\n"
                   . "• **Total Mahasiswa**: {$total} Orang\n"
                   . "• **Statistik Keaktifan**:\n"
                   . "  - Mahasiswa Aktif: **{$aktif} Orang** (" . ($total > 0 ? round(($aktif / $total) * 100) : 0) . "%)\n"
                   . "  - Cuti / Non-Aktif: **{$nonAktif} Orang**\n\n"
                   . "📋 **Rincian Status Akademik Mahasiswa**:\n";

            $no = 1;
            foreach ($users as $u) {
                $roleLabel = $this->getRoleLabel($u->role);
                $roleBadge = $u->role !== 'mahasiswa' ? " ({$roleLabel})" : "";
                $statusBadge = $u->status_akademik ?: 'Aktif';
                $reply .= "{$no}. **{$u->name}** (NPM: {$u->npm})\n"
                        . "   • Status: **{$statusBadge}** | Semester: {$u->semester}{$roleBadge}\n";
                $no++;
            }

            $reply .= "\n*Seluruh data tersinkronisasi 100% secara real-time dengan Direktori Mahasiswa di sistem web.*";
            return $reply;
        }

        // G. Total Mahasiswa & Direktori Nama Mahasiswa
        if (preg_match('/\b(?:total|jumlah)\s+(?:seluruh\s+|semua\s+)?mahasiswa\b/i', $msg) ||
            preg_match('/\b(?:daftar|direktori)\s+(?:nama\s+)?mahasiswa\b/i', $msg)) {
            $activeClassSemester = AcademicService::getActiveSemester();
            $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
            $users = User::orderBy('npm', 'asc')->get();
            $total = $users->count();

            $reply = "📋 **Direktori Mahasiswa Kelas Informatika ({$activeClassSemester} - {$kelasLabel})**:\n\n"
                   . "• **Total Mahasiswa Terdaftar**: **{$total} Orang**\n\n"
                   . "Berikut daftar seluruh mahasiswa:\n";

            $no = 1;
            foreach ($users as $u) {
                $roleLabel = $this->getRoleLabel($u->role);
                $reply .= "{$no}. **{$u->name}** (NPM: {$u->npm}) — Role: *{$roleLabel}* (Status: {$u->status_akademik})\n";
                $no++;
            }

            return $reply;
        }

        // H. Struktur Pengurus Kelas & Data Akun Role
        if (preg_match('/\b(?:struktur\s+pengurus|siapa\s+(?:saja\s+)?pengurus|pengurus\s+kelas|data\s+akun\s*(?:&|dan)?\s*role)\b/i', $msg) ||
            (preg_match('/\bpengurus\b/i', $msg) && preg_match('/\b(?:kelas|struktur|siapa)\b/i', $msg))) {
            $adminUsers = User::where('role', 'admin')->get();
            $ketuaUsers = User::where('role', 'ketua')->get();
            $bendaharaUsers = User::where('role', 'bendahara')->get();
            $mhsCount = User::where('role', 'mahasiswa')->count();
            $totalUsers = User::count();

            $adminStr = $adminUsers->map(fn($u) => "**{$u->name}** (NPM: {$u->npm})")->implode(', ') ?: 'Belum ditentukan';
            $ketuaStr = $ketuaUsers->map(fn($u) => "**{$u->name}** (NPM: {$u->npm})")->implode(', ') ?: 'Belum ditentukan';
            $bendaharaStr = $bendaharaUsers->map(fn($u) => "**{$u->name}** (NPM: {$u->npm})")->implode(', ') ?: 'Belum ditentukan';

            return "👥 **Struktur Pengurus & Data Akun Kelas Informatika**:\n\n"
                . "• **Super Admin**: {$adminStr}\n"
                . "  *(Wewenang: Hak akses penuh sistem, kelola akun pengguna, dan pengaturan semester kelas)*\n\n"
                . "• **Ketua Kelas**: {$ketuaStr}\n"
                . "  *(Wewenang: Hak akses setara Super Admin, kelola akun, semester, dan direktori kelas)*\n\n"
                . "• **Bendahara Kelas**: {$bendaharaStr}\n"
                . "  *(Wewenang: Pengelolaan Buku Kas, input pemasukan/pengeluaran, atur tarif iuran kas, dan pelaporan)*\n\n"
                . "• **Mahasiswa Reguler**: **{$mhsCount} Orang**\n"
                . "• **Total Seluruh Pengguna**: **{$totalUsers} Akun Terdaftar**";
        }

        // I. Ringkasan Lengkap Kondisi Kelas (Dahulukan sebelum filter kata kunci spesifik)
        if ((preg_match('/\b(?:ringkasan|statistik|kondisi)\b/i', $msg) && preg_match('/\bkelas\b/i', $msg)) ||
            preg_match('/\b(?:ringkasan\s+lengkap|ringkasan\s+kas|kondisi\s+kelas)\b/i', $msg)) {
            $activeClassSemester = AcademicService::getActiveSemester();
            $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
            $tahunAjaran = AcademicService::getTahunAjaran($activeClassSemester);
            $duesRate = AcademicService::getDuesRatePerWeek();
            $today = Carbon::now();
            $currentMonth = (int) $today->month;
            $namaBulan = AcademicService::getNamaBulan($currentMonth);
            $activeWeek = min(4, (int) ceil($today->day / 7));

            $pemasukanSemester = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pemasukan')->sum('amount');
            $pengeluaranSemester = KasTransaction::where('semester', $activeClassSemester)->where('type', 'pengeluaran')->sum('amount');
            $saldoSemester = $pemasukanSemester - $pengeluaranSemester;

            $totalMhs = User::count();
            $aktifMhs = User::where('status_akademik', 'Aktif')->count();

            $admin = User::where('role', 'admin')->first();
            $ketua = User::where('role', 'ketua')->first();
            $bendahara = User::where('role', 'bendahara')->first();

            $adminNameStr = $admin ? $admin->name : 'Belum ada';
            $ketuaNameStr = $ketua ? $ketua->name : 'Belum ada';
            $bendaharaNameStr = $bendahara ? $bendahara->name : 'Belum ada';

            return "📊 **Ringkasan Lengkap Kondisi Kelas Informatika**:\n\n"
                . "🎓 **Akademik & Kelas**:\n"
                . "• Semester Berjalan: **{$activeClassSemester}** ({$kelasLabel}, TA {$tahunAjaran})\n"
                . "• Total Mahasiswa: **{$totalMhs} Orang** ({$aktifMhs} Aktif)\n"
                . "• Pengurus: Admin ({$adminNameStr}), Ketua ({$ketuaNameStr}), Bendahara ({$bendaharaNameStr})\n\n"
                . "💰 **Keuangan Kas ({$activeClassSemester})**:\n"
                . "• Saldo Kas Aktif: **Rp " . number_format($saldoSemester, 0, ',', '.') . "**\n"
                . "• Pemasukan: Rp " . number_format($pemasukanSemester, 0, ',', '.') . " | Pengeluaran: Rp " . number_format($pengeluaranSemester, 0, ',', '.') . "\n"
                . "• Tarif Iuran Kas: **Rp " . number_format($duesRate, 0, ',', '.') . " / minggu**\n"
                . "• Periode Berjalan: Bulan {$namaBulan} {$today->year} (s/d Minggu ke-{$activeWeek})\n\n"
                . "*Seluruh data tersinkronisasi otomatis dengan database Buku Kas dan Direktori Mahasiswa.*";
        }

        // J. Semester Aktif Kelas & Panduan Pengaturannya
        if (preg_match('/\bsemester\b/i', $msg) && preg_match('/\baktif\b/i', $msg) && !preg_match('/\b(?:kas|saldo|iuran|arsip|ringkasan|statistik)\b/i', $msg)) {
            $activeClassSemester = AcademicService::getActiveSemester();
            $kelasLabel = AcademicService::getKelasLabel($activeClassSemester);
            $tahunAjaran = AcademicService::getTahunAjaran($activeClassSemester);

            return "🎓 **Informasi Semester Aktif Kelas & Panduan Pengaturan**:\n\n"
                . "• **Semester Aktif Saat Ini**: **{$activeClassSemester}** (Kelas {$kelasLabel}, TA {$tahunAjaran})\n\n"
                . "📌 **Cara Mengubah Semester Aktif**:\n"
                . "1. **Melalui Chatbot (Khusus Super Admin & Ketua Kelas)**:\n"
                . "   Ketik: `ubah semester aktif ke [Semester X]`\n"
                . "   *Contoh:* `ubah semester aktif ke Semester 6`\n\n"
                . "2. **Melalui Halaman Web**:\n"
                . "   • Buka menu **'Kelola Akun & Semester'** pada navigasi sidebar kiri.\n"
                . "   • Pada Panel Kotak Ungu teratas (*Pengaturan Semester Aktif Kelas*), pilih semester baru pada dropdown.\n"
                . "   • Centang sinkronisasi semester mahasiswa, lalu klik tombol **'Terapkan Semester'**.";
        }

        // Jika user secara spesifik meminta rangkuman, analisis, data, perhitungan, atau rincian keuangan (bukan tanya letak/posisi/cara akses), kembalikan null agar diproses & dirangkum oleh AI
        if (preg_match('/\b(?:rangkum|rangkuman|rekapkan|analisis|berapa|hitung|kondisi|status\s+kas|total|saldo|laporan\s+keuangan|rincian)\b/i', $msg) && 
            !preg_match('/\b(?:dimana|di\s*mana|letak|posisi|penempatan|lokasi|sebelah\s*mana|bagian\s*mana|cara\s+akses|buka\s+menu|cara\s+buka|panduan\s+ke)\b/i', $msg)) {
            return null;
        }

        $isLocationOrAccessQuery = preg_match('/\b(?:dimana|di\s*mana|dimanakah|di\s*manakah|letak|posisi|penempatan|lokasi|sebelah\s*mana|bagian\s*mana|tempat|cari\s*tombol|menemukan|taruh|akses|cara\s+akses|buka\s+menu|cara\s+buka|cara\s+ke|cara\s+menuju|cara\s+masuk|bagaimana\s+cara|gimana\s+cara|panduan\s+ke|ganti\s*bulan|sinkron(?:kan)?\s*waktu|waktu\s*asli|waktu\s*nyata|bulan\s*baru|siklus\s*bulan)\b/i', $msg);

        // Jika bukan pertanyaan letak / penempatan / posisi tombol / cara akses / panduan menu / sinkronisasi waktu, kembalikan null
        if (!$isLocationOrAccessQuery && !preg_match('/\b(?:penempatan|letak|posisi|cara\s+akses|ganti\s*bulan|waktu\s*asli)\b/i', $msg) && !preg_match('/\b(?:menu\s+arsip|menu\s+kas|menu\s+inventaris)\b/i', $msg)) {
            return null;
        }

        // 1. Tombol Ganti Sandi / Pengaturan Password Sendiri
        if (preg_match('/\b(?:sandi|password|pw)\b/i', $msg) && !preg_match('/\b(?:reset\s*sandi|sandi\s*(?:user|pengguna|orang|mahasiswa))\b/i', $msg)) {
            return "Cara Akses & Letak Tombol Pengaturan Sandi (Ubah Password Sendiri):\n\n"
                . "• **Posisi di Web**: Terletak di **Sidebar Kiri (Navigasi Kiri), di bagian paling bawah, tepat di atas tombol Keluar (Logout) dan profil pengguna**.\n"
                . "• **Tampilan Tombol**: Tombol abu-abu bertuliskan **\"Pengaturan Sandi\"** dengan ikon kunci gembok dan badge **\"Ubah PW\"**.\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Lihat ke Sidebar Navigasi di sebelah kiri layar.\n"
                . "   2. Gulir ke bagian paling bawah sidebar (tepat di atas tombol Keluar).\n"
                . "   3. Klik tombol **\"Pengaturan Sandi\"**.\n"
                . "   4. Pada pop-up modal yang muncul, masukkan sandi lama Anda saat ini, lalu masukkan sandi baru dan konfirmasi sandi baru.\n"
                . "   5. Klik tombol biru **\"Simpan Sandi Baru\"**.\n"
                . "• **Hak Akses**: Dapat diakses dan digunakan mandiri oleh seluruh pengguna (Super Admin, Bendahara, Ketua Kelas, dan Mahasiswa).";
        }

        // 2. Tombol Keluar / Logout
        if (preg_match('/\b(?:keluar|logout|log\s*out)\b/i', $msg)) {
            return "Cara Akses & Letak Tombol Keluar (Logout):\n\n"
                . "• **Posisi di Web**: Terletak di **Sidebar Kiri (Navigasi Kiri) sudut paling bawah**, persis di samping identitas nama dan avatar akun Anda.\n"
                . "• **Tampilan Tombol**: Tombol dengan ikon pintu keluar dan teks berwarna merah (**\"Keluar\"**).\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Arahkan kursor ke sudut kiri bawah layar pada Sidebar Navigasi.\n"
                . "   2. Klik tombol merah **\"Keluar\"** untuk mengakhiri sesi login akun Anda secara aman.";
        }

        // 3. Ubah / Ganti Semester Aktif
        if (preg_match('/\b(?:semester|smt)\b/i', $msg) && !preg_match('/\b(?:arsip|rekap(?:an)?|rekapitulasi)\b/i', $msg)) {
            return "Cara Akses & Letak Fitur Pengaturan Semester Aktif:\n\n"
                . "• **Posisi di Web**: Masuk ke menu **'Kelola Akun & Semester' (Menu ke-5 di Sidebar Kiri)**.\n"
                . "• **Letak Fitur**: Terletak di dalam **Panel Kotak Ungu** bertuliskan *\"Pengaturan Semester Aktif Kelas & Sinkronisasi Sistem\"* tepat di bawah judul halaman.\n"
                . "• **Tampilan**: Terdapat dropdown pilihan Semester 1 s/d 8, checkbox sinkronisasi mahasiswa aktif, dan tombol ungu **\"Terapkan Semester\"**.\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Masuk sebagai akun Super Admin (menu ini khusus Super Admin).\n"
                . "   2. Klik menu **\"Kelola Akun & Semester\"** di Sidebar Kiri.\n"
                . "   3. Pada Panel Kotak Ungu di bagian atas, pilih semester baru pada dropdown Semester 1-8.\n"
                . "   4. Centang sinkronisasi semester mahasiswa jika ingin memperbarui seluruh status mahasiswa ke semester tersebut.\n"
                . "   5. Klik tombol ungu **\"Terapkan Semester\"**.\n"
                . "• **Badge Status**: Pada **Header Atas Layar (Top App Bar sebelah kanan)** terdapat badge penunjuk semester aktif saat ini (contoh: *Semester 5 (5A)*).\n"
                . "• **Hak Akses**: Khusus Super Admin & Ketua Kelas.";
        }

        // 9. Tombol Catat Transaksi Kas (Pemasukan & Pengeluaran)
        if (preg_match('/\b(?:transaksi|kas|pemasukan|pengeluaran)\b/i', $msg) && preg_match('/\b(?:catat|tambah|input)\b/i', $msg)) {
            return "Cara Akses & Letak Tombol Catat Transaksi Kas:\n\n"
                . "• **Posisi di Web**: Masuk ke menu **'Buku Kas Kelas' (Menu ke-3 di Sidebar Kiri)**.\n"
                . "• **Letak Tombol**: Terletak di **Toolbar Header atas halaman sebelah kanan**, di samping tombol Atur Tarif Iuran.\n"
                . "• **Tampilan Tombol**: Tombol hitam pekat bertuliskan **\"+ Catat Transaksi\"**.\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Klik menu **\"Buku Kas Kelas\"** pada Sidebar Kiri.\n"
                . "   2. Klik tombol hitam **\"+ Catat Transaksi\"** di kanan atas halaman.\n"
                . "   3. Pada formulir modal, pilih tipe transaksi (Pemasukan atau Pengeluaran), masukkan nominal, tanggal, keterangan, dan opsional upload nota.\n"
                . "   4. Klik tombol simpan transaksi.\n"
                . "• **Hak Akses**: Khusus Bendahara Kelas.";
        }

        // 10. Tombol Atur Tarif Iuran Mingguan
        if (preg_match('/\b(?:tarif|iuran\s*mingguan|tarif\s*kas)\b/i', $msg)) {
            return "Cara Akses & Letak Tombol Atur Tarif Iuran Kas:\n\n"
                . "• **Posisi di Web**: Masuk ke menu **'Buku Kas Kelas' (Menu ke-3 di Sidebar Kiri)**.\n"
                . "• **Letak Tombol**: Terletak di **Toolbar Header atas halaman**, persis di sebelah badge nominal tarif kas mingguan.\n"
                . "• **Tampilan Tombol**: Tombol hijau muda bertuliskan **\"Atur Tarif Iuran\"** dengan ikon gerigi.\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Klik menu **\"Buku Kas Kelas\"** pada Sidebar Kiri.\n"
                . "   2. Klik tombol hijau muda **\"Atur Tarif Iuran\"** di toolbar kanan atas.\n"
                . "   3. Masukkan nominal tarif iuran mingguan baru (misal: 5.000 atau 10.000) lalu klik simpan.\n"
                . "   4. Sistem buku kas, rekap tunggakan, dan AI Chatbot akan otomatis sinkron dengan tarif baru.\n"
                . "• **Hak Akses**: Khusus Bendahara Kelas.";
        }

        // 11. Tombol Bayar Iuran / Batalkan Iuran (Matriks)
        if (preg_match('/\b(?:bayar|centang|matriks|iuran)\b/i', $msg)) {
            return "Cara Akses & Letak Tombol Bayar & Batalkan Iuran Kas:\n\n"
                . "• **Posisi di Web**: Masuk ke menu **'Buku Kas Kelas' (Menu ke-3 di Sidebar Kiri)**.\n"
                . "• **Letak Tombol**: Di bagian tengah halaman pada tabel **'Matriks Iuran Mingguan'**, pada **kolom paling kanan ('Aksi Iuran')** di baris masing-masing nama mahasiswa.\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Klik menu **\"Buku Kas Kelas\"** pada Sidebar Kiri.\n"
                . "   2. Gulir ke tabel **\"Matriks Iuran Mingguan\"** di bagian tengah halaman.\n"
                . "   3. Cari baris nama mahasiswa yang membayar iuran.\n"
                . "   4. Klik tombol hijau **\"Bayar\"** untuk menandai lunas minggu tertentu (Minggu 1 s/d 4), atau tombol merah **\"Batalkan\"** jika ingin membatalkan status bayar.\n"
                . "• **Hak Akses**: Khusus Bendahara Kelas.";
        }

        // 12. Tombol Edit / Hapus Transaksi Kas
        if ((preg_match('/\b(?:edit|ubah)\b/i', $msg) || preg_match('/\b(?:hapus|delete)\b/i', $msg)) && preg_match('/\b(?:transaksi|kas|mutasi)\b/i', $msg)) {
            return "Cara Akses & Letak Tombol Edit & Hapus Transaksi Kas:\n\n"
                . "• **Posisi di Web**: Masuk ke menu **'Buku Kas Kelas' (Menu ke-3 di Sidebar Kiri)**.\n"
                . "• **Letak Tombol**: Gulir ke bagian bawah halaman pada tabel **'Riwayat Transaksi Kas'**, di **kolom paling kanan ('Aksi')** pada setiap baris mutasi.\n"
                . "• **Tampilan Tombol**: Teks biru bertuliskan **\"Edit\"** dan teks merah bertuliskan **\"Hapus\"**.\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Klik menu **\"Buku Kas Kelas\"** pada Sidebar Kiri.\n"
                . "   2. Gulir ke tabel **\"Riwayat Transaksi Kas\"** di bagian paling bawah.\n"
                . "   3. Temukan transaksi yang ingin dikelola, lalu klik tombol biru **\"Edit\"** untuk mengubah data atau tombol merah **\"Hapus\"** untuk membatalkan mutasi.\n"
                . "• **Hak Akses**: Khusus Bendahara Kelas.";
        }

        // 13. Mode Arsip & Rekapitulasi Pendataan Kas (Per Bulan / Per Semester / Semua Periode)
        if (preg_match('/\b(?:arsip|rekap(?:an)?|rekapitulasi|persemester|per\s*semester|perbulan|per\s*bulan|semua\s*periode|semua\s*pendataan)\b/i', $msg)) {
            return "Cara Akses & Melihat Menu Arsip & Rekapitulasi Kas:\n\n"
                . "• **Posisi di Web**: Terletak di **Sidebar Kiri menu ke-3** bertuliskan **\"Arsip & Rekapitulasi\"**, atau via tombol pintas **\"Arsip Kas\"** di halaman Buku Kas.\n"
                . "• **Mode Rekapitulasi yang Tersedia**:\n"
                . "   1. **Per Bulan**: Menampilkan saldo, pemasukan, pengeluaran, rekap matriks iuran mingguan (M1–M4), serta riwayat mutasi bulan terpilih.\n"
                . "   2. **Per Semester**: Menampilkan ringkasan finansial semester dan rincian mutasi kas per bulan sepanjang semester terpilih.\n"
                . "   3. **Semua Periode**: Menampilkan komparasi finansial seluruh semester (Semester 1 s/d 8) dan akumulasi iuran sepanjang waktu.\n"
                . "• **Hak Akses**: Dapat dilihat oleh seluruh pengguna (Super Admin, Bendahara, Ketua Kelas, dan Mahasiswa).";
        }

        // 14. Halaman / Fitur Buku Kas Kelas Keseluruhan
        if (preg_match('/\b(?:kas|uang\s*kas|buku\s*kas)\b/i', $msg)) {
            return "Cara Akses & Letak Fitur Buku Kas Kelas:\n\n"
                . "• **Posisi di Web**: Terletak di **Sidebar Kiri menu ke-2** bertuliskan **\"Buku Kas Kelas\"**.\n"
                . "• **Fitur di Halaman Buku Kas**:\n"
                . "   • Filter Semester, Bulan, dan Tahun serta tombol pintas **\"Arsip Kas\"**.\n"
                . "   • Matriks Iuran Mingguan (Minggu 1 s/d 4) untuk memantau status lunas/belum setiap mahasiswa.\n"
                . "   • Khusus Bendahara: tombol catat transaksi, atur tarif iuran, bayar/batalkan iuran mingguan, serta ekspor Excel.\n"
                . "• **Catatan**: Ringkasan saldo, total pemasukan, total pengeluaran, dan riwayat mutasi lengkap tersedia di menu **Arsip & Rekapitulasi**.";
        }

        // 15. Tombol Tambah Akun Baru
        if (preg_match('/\b(?:tambah|buat|daftarkan)\b/i', $msg) && preg_match('/\b(?:akun|user|pengguna)\b/i', $msg)) {
            return "Cara Akses & Letak Tombol Tambah Akun Baru:\n\n"
                . "• **Posisi di Web**: Masuk ke menu **'Kelola Akun & Semester' (Menu ke-6 di Sidebar Kiri)**.\n"
                . "• **Letak Tombol**: Terletak di **Toolbar Header atas halaman sebelah kanan**.\n"
                . "• **Tampilan Tombol**: Tombol hitam bertuliskan **\"+ Tambah Akun Baru\"** dengan ikon plus (+).\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Masuk sebagai Super Admin.\n"
                . "   2. Klik menu **\"Kelola Akun & Semester\"** di Sidebar Kiri (Menu ke-6).\n"
                . "   3. Klik tombol hitam **\"+ Tambah Akun Baru\"** di toolbar kanan atas.\n"
                . "   4. Masukkan Nama Lengkap, NPM, dan pilih Peran/Role (Admin, Bendahara, Ketua, Mahasiswa).\n"
                . "   5. Klik tombol simpan akun.\n"
                . "• **Hak Akses**: Khusus Super Admin & Ketua Kelas.";
        }

        // 16. Kelola Akun Pengguna (Reset Sandi Pengguna Lain / Ubah Role / Hapus Akun)
        if (preg_match('/\b(?:kelola\s*akun|data\s*akun|reset\s*sandi|ubah\s*role|hapus\s*akun|pengguna|user)\b/i', $msg)) {
            return "Cara Akses & Letak Fitur Kelola Akun Pengguna:\n\n"
                . "• **Posisi di Web**: Terletak di **Sidebar Kiri menu ke-6** (ikon kunci/pengguna bertuliskan **\"Kelola Akun & Semester\"**).\n"
                . "• **Letak Tombol**: Di dalam tabel **Data Pengguna**, pada **kolom paling kanan** di setiap baris akun pengguna.\n"
                . "• **Tampilan Tombol**: Terdiri dari tombol biru **\"Akademik\"** (atur semester & status), tombol abu-abu **\"Sandi\"** (reset password), dan tombol merah **\"Hapus\"**.\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Masuk sebagai akun Super Admin atau Ketua Kelas (menu ini disembunyikan untuk role lain).\n"
                . "   2. Klik menu **\"Kelola Akun & Semester\"** di Sidebar Kiri (Menu ke-6).\n"
                . "   3. Gulir ke tabel **Daftar Akun Pengguna**.\n"
                . "   4. Pada kolom paling kanan baris pengguna yang dipilih, klik tombol **\"Akademik\"**, **\"Sandi\"**, atau **\"Hapus\"** sesuai kebutuhan.\n"
                . "• **Hak Akses**: Khusus Super Admin & Ketua Kelas.";
        }

        // 17. Daftar Anggota Kelas / Data Mahasiswa
        if (preg_match('/\b(?:mahasiswa|anggota|teman|murid)\b/i', $msg)) {
            return "Cara Akses Data Anggota Kelas:\n\n"
                . "• **Daftar Iuran Mahasiswa**: Tersedia pada matriks iuran mingguan di menu **\"Buku Kas Kelas\" (Menu ke-2 di Sidebar Kiri)**.\n"
                . "• **Pengelolaan Akun Mahasiswa (Tambah/Ubah Role/Reset Sandi)**: Tersedia di menu **\"Kelola Akun & Semester\" (Menu ke-4 di Sidebar Kiri)** khusus Super Admin & Ketua Kelas.";
        }

        // 18. Dashboard Kelas
        if (preg_match('/\b(?:dashboard|beranda|home)\b/i', $msg)) {
            return "Cara Akses & Letak Fitur Dashboard:\n\n"
                . "• **Posisi di Web**: Terletak di **Sidebar Kiri menu ke-1** bertuliskan **\"Dashboard\"**.\n"
                . "• **Konten Dashboard**: Menampilkan status iuran kas pribadi (4 minggu M1–M4) pada bulan dan semester aktif.\n"
                . "• **Hak Akses**: Dapat diakses oleh seluruh pengguna.";
        }

        // 19. Tombol Hamburger / Buka-Tutup Sidebar
        if (preg_match('/\b(?:hamburger|sidebar|tutup\s*menu|buka\s*menu|navigasi)\b/i', $msg)) {
            return "Cara Akses & Letak Tombol Hamburger (Buka/Tutup Sidebar):\n\n"
                . "• **Posisi di Web**: Terletak di **Top App Bar (Header paling atas layar) di sudut kiri atas**.\n"
                . "• **Tampilan**: Ikon tiga garis horizontal yang dapat diklik untuk menyembunyikan atau memunculkan sidebar navigasi.\n"
                . "• **Langkah Cara Akses**: Klik tombol tiga garis di pojok kiri atas untuk membuka atau menutup sidebar navigasi.";
        }

        // 20. Tombol Widget AI Chatbot
        if (preg_match('/\b(?:chatbot|chat|ai|bot|asisten)\b/i', $msg)) {
            return "Cara Akses & Letak Widget AI Chatbot:\n\n"
                . "• **Posisi di Web**: Mengambang (*floating*) di **sudut kanan bawah layar** di semua halaman web.\n"
                . "• **Tampilan**: Tombol lingkaran biru dengan ikon avatar asisten.\n"
                . "• **Langkah Cara Akses**:\n"
                . "   1. Klik tombol lingkaran biru di sudut kanan bawah layar untuk membuka jendela obrolan.\n"
                . "   2. Jendela chat dapat **digeser (*draggable*)** ke area layar mana pun dengan menekan dan menahan bagian header jendela obrolan.\n"
                . "   3. Ketik pertanyaan atau perintah Anda pada kolom input di bawah, lalu klik tombol kirim atau tekan Enter.\n"
                . "• **Hak Akses**: Dapat digunakan oleh seluruh pengguna sesuai perannya masing-masing.";
        }

        // 21. Siklus Bulanan & Sinkronisasi Waktu Nyata Buku Kas
        if (preg_match('/\b(?:ganti\s*bulan|ganti\s*bln|siklus\s*bulan|pindah\s*bulan|bulan\s*baru|pendataan\s*bulan\s*baru|sinkron(?:kan)?\s*waktu|waktu\s*asli|waktu\s*nyata|real\s*time)\b/i', $msg)) {
            $namaBulan = AcademicService::getNamaBulan();
            $thn = AcademicService::getRealCurrentYear();
            $mgg = AcademicService::getRealActiveWeek();
            $sem = AcademicService::getActiveSemester();

            return "Sinkronisasi Waktu Nyata & Siklus Pergantian Bulan Buku Kas:\n\n"
                . "• **Status Waktu Nyata Saat Ini**: Bulan **{$namaBulan} {$thn}** (Minggu ke-{$mgg} aktif berjalan) pada **{$sem}**.\n\n"
                . "• **Mekanisme Otomatis Saat Ganti Bulan**:\n"
                . "   1. **Otomatis Beralih Bulan**: Begitu kalender berganti bulan (misalnya dari September ke Oktober), tampilan Matriks Iuran di menu Buku Kas langsung otomatis membuka bulan baru tersebut tanpa perlu pengaturan manual.\n"
                . "   2. **Mulai Pendataan Bulan Baru**: Pada bulan baru, seluruh mahasiswa otomatis memiliki matriks 4 minggu baru dengan status awal Belum Lunas yang siap dicatat oleh Bendahara.\n"
                . "   3. **Arsip Bulan Sebelumnya Tetap Tersimpan Utuh**: Seluruh riwayat pembayaran bulan-bulan lalu tersimpan rapi dan dapat dibuka kapan saja melalui filter bulan di halaman Buku Kas.\n"
                . "   4. **Saldo Kas Bersifat Kumulatif**: Seluruh saldo kas masuk dan keluar diakumulasikan sepanjang semester aktif, sehingga dana kas dari bulan-bulan sebelumnya tetap aman dan utuh di saldo kas kelas.\n"
                . "   5. **Pencatatan Real-Time**: Transaksi dan pembayaran kas baru yang dicatat (baik via web maupun AI Chatbot) otomatis tersimpan pada tanggal dan bulan real-time saat ini.";
        }

        // 22. Pertanyaan Umum Penempatan / Letak Tombol & Cara Akses Secara Keseluruhan
        return "Panduan Lengkap Navigasi & Penempatan Fitur Web Kas Kelas:\n\n"
            . "1. Sidebar Kiri (Navigasi Utama Layar):\n"
            . "   • Menu Dashboard (ke-1): Status iuran kas bulanan pribadi (4 minggu M1–M4).\n"
            . "   • Menu Buku Kas Kelas (ke-2): Matriks iuran mingguan mahasiswa (M1–M4) dan pencatatan kas oleh Bendahara.\n"
            . "   • Menu Arsip & Rekapitulasi (ke-3): Pusat informasi saldo kas, pemasukan, pengeluaran, dan rekapitulasi Per Bulan, Per Semester, serta Semua Periode.\n"
            . "   • Menu Kelola Akun & Semester (ke-4, khusus Super Admin & Ketua Kelas): Pengaturan semester aktif, kenaikan semester massal, dan kelola akun pengguna.\n"
            . "   • Tombol 'Pengaturan Sandi' (Ubah PW Mandiri) & tombol 'Keluar / Logout' di bagian bawah sidebar.\n\n"
            . "2. Header Atas Layar (Top App Bar):\n"
            . "   • Sudut Kiri: Tombol Hamburger Menu (buka/tutup sidebar navigasi).\n"
            . "   • Sudut Kanan: Jam real-time, Badge Semester Aktif, dan Badge Role login.\n\n"
            . "3. Widget AI Chatbot:\n"
            . "   • Mengambang di sudut kanan bawah layar pada semua halaman, dan dapat digeser (draggable) posisinya di layar.";
    }
}
