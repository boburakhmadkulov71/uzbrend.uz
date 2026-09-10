<?php
// bot.php
$botToken = "8820976937:AAFK2znnSNxRuMPEkOmzXClGeWRfR6ngCGs";
$adminId = "8543483836"; // O'zingizning Telegram chat ID yoki guruh ID raqamingiz
$miniAppUrl = "https://uzbrend.uz/calc.html"; // calc.html yuklangan havola

$website = "https://api.telegram.org/bot".$botToken;

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    exit();
}

$chatId = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? null;
$contact = $update["message"]["contact"] ?? null;
$webAppData = $update["message"]["web_app_data"]["data"] ?? null;

// Funksiya: Xabar yuborish
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

// Bosh menyu klaviaturasi
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
            ['text' => '📞 Dolzarb Narxni Bilish / Bog\'lanish', 'request_contact' => true]
        ],
        [
            ['text' => '🌐 Rasmiy Saytimiz']
        ]
    ],
    'resize_keyboard' => true
];

// /start buyrug'i
if ($text == "/start") {
    $welcome = "Assalomu alaykum! <b>Uz Brend va Uz Beton</b> rasmiy botiga xush kelibsiz.\n\n"
             . "Bu yerda siz:\n"
             . "• Qurilishingiz uchun g'isht va beton hajmini hisoblashingiz;\n"
             . "• Zavodlarimiz lokatsiyasini olishingiz;\n"
             . "• Bugungi kundagi eng dolzarb narxlarni bilishingiz mumkin.";
    sendMessage($chatId, $welcome, $mainMenu);
}

// 1. MINI APP'DAN KELGAN HISOB-KITOB NATIJASI
elseif ($webAppData) {
    $data = json_decode($webAppData, true);
    
    if ($data['tur'] == "G'isht") {
        $msg = "✅ <b>G'isht hisob-kitobi qabul qilindi:</b>\n\n"
             . "📏 O'lcham: {$data['uzunlik']}m x {$data['balandlik']}m ({$data['qalinlik']})\n"
             . "📐 Umumiy maydon: {$data['maydon']}\n"
             . "🧱 <b>Kerakli g'isht: {$data['jami']}</b>\n\n"
             . "<i>Bugungi kundagi narx va yetkazib berish shartlarini bilish uchun quyidagi <b>'📞 Dolzarb Narxni Bilish'</b> tugmasini bosing.</i>";
    } else {
        $msg = "✅ <b>Beton hisob-kitobi qabul qilindi:</b>\n\n"
             . "📏 O'lcham: {$data['uzunlik']}m x {$data['kenglik']}m x {$data['balandlik']}m\n"
             . "🚚 <b>Kerakli beton: {$data['jami']}</b>\n\n"
             . "<i>Bugungi kundagi narx va yetkazib berish shartlarini bilish uchun quyidagi <b>'📞 Dolzarb Narxni Bilish'</b> tugmasini bosing.</i>";
    }
    
    sendMessage($chatId, $msg, $mainMenu);
}

// 2. MIJOZ KONTAKT YUBORGANDA (BUYURTMA / ZAYAVKA)
elseif ($contact) {
    $phone = $contact['phone_number'];
    $userName = $contact['first_name'] ?? 'Mijoz';

    // Foydalanuvchiga tasdiq
    sendMessage($chatId, "Rahmat, <b>{$userName}</b>! Telefon raqamingiz qabul qilindi.\nTez orada mutaxassisimiz siz bilan bog'lanib, eng so'nggi narxlarni ma'lum qiladi.", $mainMenu);

    // Adminga xabarnoma yuborish
    $adminAlert = "🔔 <b>YANGI MUROJAAT (ZAYAVKA)!</b>\n\n"
                . "👤 <b>Ism:</b> {$userName}\n"
                . "📱 <b>Telefon:</b> {$phone}\n"
                . "🆔 <b>Telegram ID:</b> {$chatId}";
    sendMessage($adminId, $adminAlert);
    
    sendMessage($chatId, "Agar siz hoziroq bog'lanishni hohlasangiz qo'ng'rioq qiling:\n\nUz G'isht:\n+9980115122\n\nUz Beton:\n+998770009594", $mainMenu);
}

// 3. MAHSULOTLAR TUGMASI
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
              . "• Avtomixerr xizmati\n"
              . "⚙️ <b>Xitoy Stanoklari:</b>\n"
              . "• Siz hohlagan turdagi uskunalari hamda tehnikalar to'g'ridan-to'g'ri importi\n\n"
              . "<i>Dolzarb narxlar kunlik xomashyo narxlariga qarab belgilanadi. Pastdagi tugma orqali bog'lanishingiz mumkin.</i>";
    sendMessage($chatId, $products, $mainMenu);
}

// 4. LOKATSIYA TUGMASI
elseif ($text == "📍 Zavodlar Lokatsiyasi") {
    $locations = "📍 <b>Zavodlarimiz manzillari:</b>\n\n"
               . "<b>Uz Beton Rishton:</b>\n"
               . "• <a href='https://maps.app.goo.gl/dUBMBxecYKFXDzqv6'>Google Xaritada ochish</a>\n"
               . "• <a href='https://yandex.uz/maps/-/CTT1EIpa'>Yandex Xaritada ochish</a>\n\n"
               . "<b>Uz Brend G'isht Zavodi:</b>\n"
               . "• <a href='https://maps.app.goo.gl/LYwwGTLEqgcQoZvW6'>Google Xaritada ochish</a>\n"
               . "• <a href='https://yandex.uz/maps/-/CTT1E0pk'>Yandex Xaritada ochish</a>\n"
    sendMessage($chatId, $locations, $mainMenu);
}

// 5. SAYT HAVOLASI
elseif ($text == "🌐 Rasmiy Saytimiz") {
    sendMessage($chatId, "Rasmiy veb-saytimiz: https://uzbrend.uz", $mainMenu);
}
