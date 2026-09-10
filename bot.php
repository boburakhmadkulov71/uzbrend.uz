<?php
// bot.php
$botToken = "8820976937:AAFK2znnSNxRuMPEkOmzXClGeWRfR6ngCGs";
$adminId = "8978641805"; // "Uz Brend | ARIZALAR" guruh/chat ID raqami
$miniAppUrl = "https://uzbrend.uz/calc.html";

$website = "https://api.telegram.org/bot".$botToken;

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    exit();
}

$message = $update["message"] ?? null;
$callbackQuery = $update["callback_query"] ?? null;

$chatId = $message["chat"]["id"] ?? $callbackQuery["message"]["chat"]["id"] ?? null;
$text = $message["text"] ?? null;
$contact = $message["contact"] ?? null;
$webAppData = $message["web_app_data"]["data"] ?? null;
$callbackData = $callbackQuery["data"] ?? null;

// Foydalanuvchi raqamini saqlash va tekshirish (users.json orqali)
$dbFile = __DIR__ . '/users.json';
function getUserPhone($chatId, $dbFile) {
    if (!file_exists($dbFile)) return null;
    $data = json_decode(file_get_contents($dbFile), true) ?: [];
    return $data[$chatId]['phone'] ?? null;
}

function saveUser($chatId, $name, $phone, $dbFile) {
    $data = file_exists($dbFile) ? (json_decode(file_get_contents($dbFile), true) ?: []) : [];
    $data[$chatId] = [
        'name' => $name,
        'phone' => $phone,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    file_put_contents($dbFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Xabar yuborish funksiyasi (cURL orqali)
function sendMessage($chatId, $message, $keyboard = null) {
    global $website;
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];
    if ($keyboard) {
        $postData['reply_markup'] = json_encode($keyboard);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $website . "/sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// Callback javob qaytarish
function answerCallback($callbackQueryId) {
    global $website;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $website . "/answerCallbackQuery");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['callback_query_id' => $callbackQueryId]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

// Kontakt so'rash klaviaturasi
$contactKeyboard = [
    'keyboard' => [
        [
            ['text' => '📱 Raqamni yuborish', 'request_contact' => true]
        ]
    ],
    'resize_keyboard' => true,
    'one_time_keyboard' => true
];

// Asosiy menyu
$mainMenu = [
    'keyboard' => [
        [
            ['text' => '🧮 Qurilish Kalkulyatori', 'web_app' => ['url' => $miniAppUrl]]
        ],
        [
            ['text' => '🧱 Mahsulotlar'],
            ['text' => '📍 Zavodlar Lokatsiyasi']
        ],
        [
            ['text' => '📞 Dolzarb Narxni Bilish']
        ],
        [
            ['text' => '🌐 Rasmiy Saytimiz']
        ]
    ],
    'resize_keyboard' => true
];

// Qiziqish yo'nalishlari (Inline tugmalar)
$categoriesInlineKeyboard = [
    'inline_keyboard' => [
        [
            ['text' => '🏗 Beton', 'callback_data' => 'cat_beton'],
            ['text' => '🧱 G\'ishtlar', 'callback_data' => 'cat_gisht']
        ],
        [
            ['text' => '⚙️ Xitoy stanoklari', 'callback_data' => 'cat_stanok'],
            ['text' => '📦 Barchasi', 'callback_data' => 'cat_all']
        ]
    ]
];

$userPhone = getUserPhone($chatId, $dbFile);

// 1. /start BUYRUG'I
if ($text == "/start") {
    if ($userPhone) {
        $welcome = "Assalomu alaykum! <b>Uz Brend va Uz Beton</b> rasmiy botiga xush kelibsiz.\n\n"
                 . "Kerakli bo'limni quyidagi menyudan tanlang:";
        sendMessage($chatId, $welcome, $mainMenu);
    } else {
        $welcome = "Assalomu alaykum! <b>Uz Brend va Uz Beton</b> rasmiy botiga xush kelibsiz.\n\n"
                 . "Bot xizmatlaridan foydalanish va dolzarb narxlarni olish uchun quyidagi tugma orqali <b>telefon raqamingizni yuboring:</b>";
        sendMessage($chatId, $welcome, $contactKeyboard);
    }
}

// 2. FOYDALANUVCHI RAQAMINI YUBORGANDA
elseif ($contact) {
    $phone = $contact['phone_number'];
    if (strpos($phone, '+') !== 0) $phone = '+' . $phone;
    $userName = htmlspecialchars($contact['first_name'] ?? 'Mijoz');

    saveUser($chatId, $userName, $phone, $dbFile);

    // Foydalanuvchiga tasdiq
    $confirmMsg = "Rahmat, <b>{$userName}</b>! Telefon raqamingiz qabul qilindi.\n\n"
                . "Endi kalkulyatordan hisoblashingiz yoki mahsulotlar bo'yicha ariza qoldirishingiz mumkin:";
    sendMessage($chatId, $confirmMsg, $mainMenu);

    // Adminga xabarnoma yuborish
    $adminAlert = "🔔 <b>YANGI FOYDALANUVCHI (RO'YXATDAN O'TDI):</b>\n\n"
                . "👤 <b>Ism:</b> {$userName}\n"
                . "📞 <b>Telefon:</b> {$phone}\n"
                . "🆔 <b>Telegram ID:</b> <code>{$chatId}</code>";
    sendMessage($adminId, $adminAlert);
}

// 3. KALKULYATOR (MINI APP) NATIJASI KELGANDA
elseif ($webAppData) {
    $data = json_decode($webAppData, true);
    $userName = htmlspecialchars($message['from']['first_name'] ?? 'Mijoz');
    $phoneText = $userPhone ? $userPhone : "Raqam yuborilmagan";

    if ($data['tur'] == "G'isht") {
        $userMsg = "✅ <b>G'isht hisob-kitobingiz qabul qilindi va mutaxassisga yo'naltirildi!</b>\n\n"
                 . "📏 O'lcham: {$data['uzunlik']}m x {$data['balandlik']}m ({$data['qalinlik']})\n"
                 . "📐 Umumiy maydon: {$data['maydon']}\n"
                 . "🧱 <b>Kerakli g'isht: {$data['jami']}</b>\n\n"
                 . "<i>Tez orada mutaxassisimiz raqamingizga bog'lanib, eng so'nggi narxlarni ma'lum qiladi.</i>\n\n"
                 . "Shoshilinch bo'lsa:\n📞 Uz G'isht: +9980115122";

        $adminMsg = "🧱 <b>YANGI ARIZA (G'ISHT KALKULYATORI):</b>\n\n"
                  . "👤 <b>Ismi:</b> {$userName}\n"
                  . "📞 <b>Tel:</b> {$phoneText}\n"
                  . "🧱 <b>G'isht turi:</b> {$data['qalinlik']}\n"
                  . "📏 <b>O'lcham:</b> {$data['uzunlik']}m x {$data['balandlik']}m\n"
                  . "📐 <b>Maydon:</b> {$data['maydon']}\n"
                  . "🔢 <b>Soni/Hajmi:</b> {$data['jami']}\n"
                  . "🆔 <b>Telegram ID:</b> <code>{$chatId}</code>";
    } else {
        $userMsg = "✅ <b>Beton hisob-kitobingiz qabul qilindi va mutaxassisga yo'naltirildi!</b>\n\n"
                 . "📏 O'lcham: {$data['uzunlik']}m x {$data['kenglik']}m x {$data['balandlik']}m\n"
                 . "🚚 <b>Kerakli beton: {$data['jami']}</b>\n\n"
                 . "<i>Tez orada mutaxassisimiz raqamingizga bog'lanib, eng so'nggi narxlarni ma'lum qiladi.</i>\n\n"
                 . "Shoshilinch bo'lsa:\n📞 Uz Beton: +998770009594";

        $adminMsg = "🏗 <b>YANGI ARIZA (BETON KALKULYATORI):</b>\n\n"
                  . "👤 <b>Ismi:</b> {$userName}\n"
                  . "📞 <b>Tel:</b> {$phoneText}\n"
                  . "🏢 <b>Mahsulot/Xizmat:</b> Tayyor Beton\n"
                  . "📏 <b>O'lchamlari:</b> {$data['uzunlik']}m x {$data['kenglik']}m x {$data['balandlik']}m\n"
                  . "🔢 <b>Soni/Hajmi:</b> {$data['jami']}\n"
                  . "🆔 <b>Telegram ID:</b> <code>{$chatId}</code>";
    }

    sendMessage($chatId, $userMsg, $mainMenu);
    sendMessage($adminId, $adminMsg);
}

// 4. "DOLZARB NARXNI BILISH" BOSILGANDA (YO'NALISH SO'RASH)
elseif ($text == "📞 Dolzarb Narxni Bilish" || $text == "📞 Dolzarb Narxni Bilish / Bog'lanish") {
    if (!$userPhone) {
        sendMessage($chatId, "Iltimos, narxlarni bilish uchun avval telefon raqamingizni yuboring:", $contactKeyboard);
    } else {
        sendMessage($chatId, "Sizni aynan qaysi mahsulotlarimiz qiziqtiradi?", $categoriesInlineKeyboard);
    }
}

// 5. INLINE TUGMADAN YO'NALISH TANLANGANDA -> ARIZALARGA YUBORISH
elseif ($callbackData) {
    answerCallback($callbackQuery["id"]);
    $from = $callbackQuery["from"];
    $userName = htmlspecialchars($from['first_name'] ?? 'Mijoz');
    $phoneText = $userPhone ? $userPhone : "Raqam mavjud emas";

    $categories = [
        'cat_beton' => "🏗 Beton",
        'cat_gisht' => "🧱 G'ishtlar",
        'cat_stanok' => "⚙️ Xitoy stanoklari",
        'cat_all' => "📦 Barchasi"
    ];

    $chosenCat = $categories[$callbackData] ?? "Qurilish mollari";

    // Mijozga tasdiq va to'g'ridan-to'g'ri raqamlar
    $replyToUser = "✅ Qabul qilindi! <b>{$chosenCat}</b> bo'yicha mutaxassisimiz tez orada siz bilan bog'lanadi.\n\n"
                 . "Agar siz hoziroq bog'lanishni xohlasangiz qo'ng'iroq qiling:\n\n"
                 . "🧱 <b>Uz G'isht:</b>\n+9980115122\n\n"
                 . "🏗 <b>Uz Beton:</b>\n+998770009594";
    sendMessage($chatId, $replyToUser, $mainMenu);

    // ARIZALAR GURUHIGA YUBORISH
    $adminNotice = "🔔 <b>YANGI ARIZA (DOLZARB NARX SO'ROVI):</b>\n\n"
                 . "👤 <b>Ismi:</b> {$userName}\n"
                 . "📞 <b>Tel:</b> {$phoneText}\n"
                 . "🏢 <b>Qiziqqan mahsuloti:</b> {$chosenCat}\n"
                 . "💬 <b>Izoh:</b> Narx va shartlarni bilmoqchi\n"
                 . "🆔 <b>Telegram ID:</b> <code>{$chatId}</code>";
    sendMessage($adminId, $adminNotice);
}

// 6. MAHSULOTLAR TUGMASI
elseif ($text == "🧱 Mahsulotlar") {
    $products = "🏭 <b>Bizning asosiy mahsulotlarimiz:</b>\n\n"
              . "🧱 <b>Uz Brend G'ishtlari:</b>\n"
              . "• Yuqori sifatli 'Paltarashka' 250 x 120 x 88 mm va 'Standart' 250 x 120 x 65 mm pishgan g'ishtlar (M100, M125)\n"
              . "• To'g'ridan-to'g'ri ishlab chiqaruvchidan yetkazib berish\n\n"
              . "🏗 <b>Uz Beton Mahsulotlari:</b>\n"
              . "• Barcha markadagi tayyor beton qorishmalari\n"
              . "• Beton plitalar\n"
              . "• Beton kolodetslar\n"
              . "• Beton FBS-bloklar\n"
              . "• Beton stolbalar\n"
              . "• Beton lotoklar\n"
              . "• Avtokran xizmati\n"
              . "• Avtobetonanasos xizmati\n"
              . "• Avtomanipulyator xizmati\n"
              . "• Avtomixerr xizmati\n\n"
              . "⚙️ <b>Xitoy Stanoklari:</b>\n"
              . "• Siz xohlagan turdagi uskunalar hamda texnikalar to'g'ridan-to'g'ri importi\n\n"
              . "<i>Dolzarb narxlar kunlik xomashyo narxlariga qarab belgilanadi. Pastdagi tugma orqali bog'lanishingiz mumkin.</i>";
    sendMessage($chatId, $products, $mainMenu);
}

// 7. LOKATSIYA TUGMASI
elseif ($text == "📍 Zavodlar Lokatsiyasi") {
    $locations = "📍 <b>Zavodlarimiz manzillari:</b>\n\n"
               . "<b>Uz Beton Rishton:</b>\n"
               . "• <a href='https://maps.app.goo.gl/dUBMBxecYKFXDzqv6'>Google Xaritada ochish</a>\n"
               . "• <a href='https://yandex.uz/maps/-/CTT1EIpa'>Yandex Xaritada ochish</a>\n\n"
               . "<b>Uz Brend G'isht Zavodi:</b>\n"
               . "• <a href='https://maps.app.goo.gl/LYwwGTLEqgcQoZvW6'>Google Xaritada ochish</a>\n"
               . "• <a href='https://yandex.uz/maps/-/CTT1E0pk'>Yandex Xaritada ochish</a>";
    sendMessage($chatId, $locations, $mainMenu);
}

// 8. SAYT HAVOLASI
elseif ($text == "🌐 Rasmiy Saytimiz") {
    sendMessage($chatId, "Rasmiy veb-saytimiz: https://uzbrend.uz", $mainMenu);
}
