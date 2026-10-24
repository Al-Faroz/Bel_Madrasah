<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Database.php';

function h(mixed $value): string
{
    // PHP mengubah key array numerik seperti '1' menjadi int 1.
    // Escape harus aman untuk angka, string, dan nilai null dari database/form.
    if ($value === null) {
        $value = '';
    } elseif (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
        $value = '';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$weekdays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
$pasarans = ['Legi', 'Pahing', 'Pon', 'Wage', 'Kliwon'];
$weekOptions = ['1' => 'Minggu ke-1', '2' => 'Minggu ke-2', '3' => 'Minggu ke-3', '4' => 'Minggu ke-4', '5' => 'Minggu ke-5', 'last' => 'Minggu terakhir'];
$db = null;
$error = '';
$message = '';
$selectedPackageId = filter_var($_POST['package_id'] ?? $_GET['package_id'] ?? 0, FILTER_VALIDATE_INT);
$selectedPackageId = $selectedPackageId !== false && $selectedPackageId > 0 ? $selectedPackageId : 0;
$editingRuleId = 0;
$ruleForm = ['weekday' => '1', 'rule_mode' => 'normal', 'special_type' => 'pasaran', 'special_value' => 'Legi', 'is_enabled' => '1'];
$eventFormErrors = [];
$copyPreview = null;
$copySourceWeekday = (int) ($_POST['source_weekday'] ?? 1);
$copyTargetWeekday = (int) ($_POST['target_weekday'] ?? 2);
$audioAssets = [];

try {
    $db = new Database();
    $db->ensureDefaultSchedulePackage();
    $packages = $db->getSchedulePackages();
    $audioAssets = $db->listAudioAssets();
    if ($selectedPackageId === 0 && $packages !== []) {
        foreach ($packages as $package) {
            if ((int) $package['is_active'] === 1) {
                $selectedPackageId = (int) $package['id'];
                break;
            }
        }
        if ($selectedPackageId === 0) {
            $selectedPackageId = (int) $packages[0]['id'];
        }
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $action = (string) ($_POST['editor_action'] ?? '');
        if ($action === 'preview_copy_schedule') {
            $sourceWeekday = $copySourceWeekday;
            $targetWeekday = $copyTargetWeekday;
            if ($sourceWeekday < 1 || $sourceWeekday > 7 || $targetWeekday < 1 || $targetWeekday > 7
                || $sourceWeekday === $targetWeekday) {
                throw new InvalidArgumentException('Hari sumber dan tujuan harus valid dan berbeda.');
            }

            $sourceRules = $db->getDayRules($selectedPackageId, $sourceWeekday);
            $targetRules = $db->getDayRules($selectedPackageId, $targetWeekday);
            if ($sourceRules === []) {
                throw new InvalidArgumentException('Hari sumber belum memiliki aturan. Buat aturan sumber terlebih dahulu; data tujuan tidak diubah.');
            }

            $sourceRulePreview = [];
            $sourceEvents = [];
            $targetEventCount = 0;
            foreach ($sourceRules as $sourceRule) {
                $specialLabel = '';
                if ($sourceRule['rule_mode'] === 'special') {
                    $specialLabel = $sourceRule['special_type'] === 'pasaran'
                        ? 'Pasaran: ' . (string) $sourceRule['special_value']
                        : 'Minggu kalender: ' . ($weekOptions[(string) $sourceRule['special_value']] ?? (string) $sourceRule['special_value']);
                }
                $ruleLabel = $sourceRule['rule_mode'] === 'normal' ? 'Normal' : 'Khusus · ' . $specialLabel;
                $events = $db->getBellEvents((int) $sourceRule['id']);
                $sourceRulePreview[] = [
                    'label' => $ruleLabel,
                    'is_enabled' => (int) $sourceRule['is_enabled'],
                    'event_count' => count($events),
                ];
                foreach ($events as $event) {
                    $event['rule_label'] = $ruleLabel;
                    $sourceEvents[] = $event;
                }
            }
            foreach ($targetRules as $targetRule) {
                $targetEventCount += count($db->getBellEvents((int) $targetRule['id']));
            }

            $copyPreview = [
                'source_weekday' => $sourceWeekday,
                'target_weekday' => $targetWeekday,
                'source_rules' => $sourceRulePreview,
                'source_rule_count' => count($sourceRules),
                'target_rule_count' => count($targetRules),
                'events' => $sourceEvents,
                'target_count' => $targetEventCount,
            ];
        } elseif ($action === 'confirm_copy_schedule') {
            $sourceWeekday = $copySourceWeekday;
            $targetWeekday = $copyTargetWeekday;
            $copyResult = $db->replaceWeekdayScheduleFromSource($selectedPackageId, $sourceWeekday, $targetWeekday);
            // Setelah salin, arahkan filter daftar pola ke hari tujuan supaya hasilnya langsung terlihat.
            $ruleForm['weekday'] = (string) $targetWeekday;
            $sourceDayName = $weekdays[$sourceWeekday] ?? 'hari sumber';
            $targetDayName = $weekdays[$targetWeekday] ?? 'hari tujuan';
            $message = 'Jadwal ' . $targetDayName . ' berhasil disalin dari ' . $sourceDayName . ': '
                . $copyResult['rules'] . ' aturan dan ' . $copyResult['events']
                . ' kegiatan. Daftar pola sekarang menampilkan ' . $targetDayName . '. Hari sumber dan hari lain tidak diubah.';
        } elseif ($action === 'save_rule') {
            $editingRuleId = (int) ($_POST['rule_id'] ?? 0);
            $ruleForm = [
                'weekday' => (string) ($_POST['weekday'] ?? '1'),
                'rule_mode' => (string) ($_POST['rule_mode'] ?? 'normal'),
                'special_type' => (string) ($_POST['special_type'] ?? 'pasaran'),
                'special_value' => (string) ($_POST['special_value'] ?? 'Legi'),
                'is_enabled' => isset($_POST['is_enabled']) ? '1' : '0',
            ];
            $ruleId = $db->saveDayRule(
                $editingRuleId > 0 ? $editingRuleId : null,
                $selectedPackageId,
                (int) $ruleForm['weekday'],
                $ruleForm['rule_mode'],
                $ruleForm['rule_mode'] === 'special' ? $ruleForm['special_type'] : null,
                $ruleForm['rule_mode'] === 'special' ? $ruleForm['special_value'] : null,
                $ruleForm['is_enabled'] === '1'
            );
            $message = $editingRuleId > 0 ? 'Aturan hari berhasil diperbarui.' : 'Aturan hari berhasil ditambahkan.';
            // Pertahankan hari yang baru disimpan agar kartu aturan tidak tampak menghilang setelah submit.
            $savedWeekday = $ruleForm['weekday'];
            $editingRuleId = 0;
            $ruleForm = ['weekday' => $savedWeekday, 'rule_mode' => 'normal', 'special_type' => 'pasaran', 'special_value' => 'Legi', 'is_enabled' => '1'];
        } elseif ($action === 'edit_rule') {
            $editingRuleId = (int) ($_POST['rule_id'] ?? 0);
            $rule = $db->getDayRule($editingRuleId);
            if ($rule === null || (int) $rule['package_id'] !== $selectedPackageId) {
                throw new InvalidArgumentException('Aturan hari yang dipilih tidak ditemukan.');
            }
            $ruleForm = [
                'weekday' => (string) $rule['weekday'],
                'rule_mode' => (string) $rule['rule_mode'],
                'special_type' => (string) ($rule['special_type'] ?? 'pasaran'),
                'special_value' => (string) ($rule['special_value'] ?? 'Legi'),
                'is_enabled' => (string) $rule['is_enabled'],
            ];
        } elseif ($action === 'save_event') {
            $ruleId = (int) ($_POST['rule_id'] ?? 0);
            $rule = $db->getDayRule($ruleId);
            if ($rule === null || (int) $rule['package_id'] !== $selectedPackageId) {
                throw new InvalidArgumentException('Aturan hari untuk kegiatan tidak ditemukan.');
            }
            // Pertahankan filter hari setelah simpan, termasuk ketika validasi kegiatan gagal.
            $ruleForm['weekday'] = (string) $rule['weekday'];
            $eventId = (int) ($_POST['event_id'] ?? 0);
            $eventName = trim((string) ($_POST['event_name'] ?? ''));
            $eventTime = trim((string) ($_POST['event_time'] ?? ''));
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);

            // Validate every event field before saving anything to SQLite.
            // Audio is selected from the managed asset list; the source file is never uploaded or copied.
            if ($eventName === '' || strlen($eventName) > 100) {
                throw new InvalidArgumentException('Nama kegiatan wajib diisi dan maksimal 100 karakter.');
            }
            if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $eventTime)) {
                throw new InvalidArgumentException('Waktu harus valid dengan format 00:00 sampai 23:59.');
            }
            if ($sortOrder < 0 || $sortOrder > 9999) {
                throw new InvalidArgumentException('Urutan harus antara 0 dan 9999.');
            }

            // Keep the existing audio reference unless the operator selects a managed
            // asset or explicitly clears the reference. Old absolute paths remain valid.
            $audioPath = '';
            if ($eventId > 0) {
                $existingEvent = $db->getBellEvent($eventId);
                if ($existingEvent === null || (int) $existingEvent['day_rule_id'] !== $ruleId) {
                    throw new InvalidArgumentException('Kegiatan yang akan diubah tidak ditemukan pada aturan hari ini.');
                }
                $audioPath = (string) $existingEvent['audio_path'];
            }

            $selectedAudioAsset = trim((string) ($_POST['audio_asset'] ?? ''));
            if ($selectedAudioAsset === '__clear__') {
                $audioPath = '';
            } elseif ($selectedAudioAsset !== '') {
                $audioPath = $db->resolveAudioAsset($selectedAudioAsset);
            }

            $db->saveBellEvent(
                $eventId > 0 ? $eventId : null,
                $ruleId,
                $eventName,
                $eventTime,
                $audioPath,
                isset($_POST['event_enabled']),
                $sortOrder
            );
            $message = $eventId > 0 ? 'Kegiatan bel berhasil diperbarui.' : 'Kegiatan bel berhasil ditambahkan.';
        } elseif ($action === 'delete_event') {
            $ruleId = (int) ($_POST['rule_id'] ?? 0);
            $rule = $db->getDayRule($ruleId);
            if ($rule === null || (int) $rule['package_id'] !== $selectedPackageId) {
                throw new InvalidArgumentException('Aturan hari untuk kegiatan tidak ditemukan.');
            }
            // Ambil identitas kegiatan sebelum dihapus agar pesan hasilnya spesifik.
            $ruleForm['weekday'] = (string) $rule['weekday'];
            $eventId = (int) ($_POST['event_id'] ?? 0);
            $event = $db->getBellEvent($eventId);
            if ($event === null || (int) $event['day_rule_id'] !== $ruleId) {
                throw new InvalidArgumentException('Kegiatan yang akan dihapus tidak ditemukan pada pola hari ini.');
            }
            $eventName = trim((string) $event['name']);
            $weekdayName = $weekdays[(int) $rule['weekday']] ?? 'hari terpilih';
            $patternName = $rule['rule_mode'] === 'normal'
                ? 'Normal'
                : 'Khusus · ' . ($rule['special_type'] === 'pasaran'
                    ? (string) $rule['special_value']
                    : (($weekOptions[(string) $rule['special_value']] ?? (string) $rule['special_value'])));
            $db->deleteBellEvent($eventId, $ruleId);
            $message = 'Berhasil menghapus kegiatan bel “' . $eventName . '” dari ' . $weekdayName . ' · ' . $patternName
                . '. Hanya kegiatan ini yang dihapus; pola hari dan kegiatan bel lainnya tetap tersimpan.';
        } else {
            throw new InvalidArgumentException('Aksi editor jadwal tidak dikenali.');
        }
    }

    $selectedPackage = $db->getSchedulePackage($selectedPackageId);
    if ($selectedPackage === null) {
        throw new RuntimeException('Paket jadwal yang dipilih tidak tersedia.');
    }
    // Database yang masih benar-benar kosong mendapat pola awal yang bisa diedit.
    // Seed hanya berjalan sekali dan tidak menimpa aturan yang sudah dibuat pengguna.
    $starterScheduleCreated = $db->ensureStarterScheduleRules($selectedPackageId);
    if ($starterScheduleCreated) {
        $message = 'Pola awal dibuat mengikuti pengaturan hari libur paket ini. Waktu bel masih berupa contoh—silakan sesuaikan dengan jadwal madrasah.';
    }
    $rules = $db->getDayRules($selectedPackageId);
    $eventsByRule = [];
    foreach ($rules as $rule) {
        $eventsByRule[(int) $rule['id']] = $db->getBellEvents((int) $rule['id']);
    }
} catch (Throwable $exception) {
    $error = $exception instanceof InvalidArgumentException
        ? $exception->getMessage()
        : 'Editor jadwal gagal memproses data: ' . $exception->getMessage();
    $packages = $packages ?? [];
    $audioAssets = $audioAssets ?? [];
    $selectedPackage = isset($db, $selectedPackageId) && $db instanceof Database ? $db->getSchedulePackage($selectedPackageId) : null;
    $rules = isset($db, $selectedPackageId) && $db instanceof Database ? $db->getDayRules($selectedPackageId) : [];
    $eventsByRule = [];
    if ($db instanceof Database) {
        foreach ($rules as $rule) {
            $eventsByRule[(int) $rule['id']] = $db->getBellEvents((int) $rule['id']);
        }
    }
}

$now = new DateTimeImmutable('now');
$documentTitle = 'Editor Jadwal Harian — Bel Madrasah';
$pageEyebrow = 'PENGELOLAAN JADWAL';
$pageHeading = 'Editor Jadwal';
$pageSubtitle = 'Atur pola hari dan kegiatan bel dalam setiap paket';
$pageStatusLabel = 'EDITOR JADWAL';
$activePage = 'schedule-editor';
$footerPageLabel = 'Editor Jadwal';
$footerPackageLabel = $selectedPackage !== null ? (string) $selectedPackage['name'] : 'Paket belum tersedia';
$footerMeta = $footerPackageLabel . ' · ' . $now->format('d/m/Y H:i');
$viewPath = __DIR__ . '/views/admin/schedule-editor.php';

require __DIR__ . '/layouts/admin.php';
