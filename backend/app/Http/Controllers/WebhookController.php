<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    public function tg (Request $request) {
        $update = Telegram::getWebhookUpdate();

        if (isset($update['message'])) {
            $message = $update['message'];

            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            $caption = "**Добро пожаловать в CoinQuest! 🤖**

>Ваш личный помощник внутри Telegram, который превращает хаос трейдинга в чёткую систему!🎓

Что вас ждет уже сегодня:

**VIP-сигналы** — *повторяйте сделки профессионалов и получайте прибыль.*

**ИИ-ассистент 24/7** — *индивидуальный помощник, который поможет принять верное решение в любое время суток!*

**Трейдинг-турниры** — *прокачивайте навыки и соревнуйтесь в реальном времени. (уже в сентябре!)*

**Обучение** — _сложная информация в легкой подаче. Для любого IQ._

**Живой чат участников** — *сообщество единомышленников и постоянная поддержка.*

**Идеи от топов рынка** — *сетапы и инсайты от практиков + возможность общаться с профессионалами напрямую.*

CoinQuest — Это путешествие в мир трейдинга, где у вас всегда есть структура, поддержка и команда рядом. Мы ждём вас!";

            $escape_chars = ['[', ']', '(', ')', '~', '`', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
            foreach ($escape_chars as $char) {
                $caption = str_replace($char, '\\' . $char, $caption);
            }

            if (trim($text) === '/start') {
                Telegram::sendPhoto([
                    'chat_id' => $chatId,
                    'caption'    => $caption,
                    'parse_mode' => 'MarkdownV2',
                    "photo" => InputFile::create(Storage::disk("public")->path("message.jpg")),
                    "reply_markup" => json_encode([
                        "inline_keyboard" => [
                            [
                                [
                                    "text" => "Открыть веб-приложение",
                                    "web_app" => [
                                        "url" => "https://" . env("DOMAIN") . "?s=profile",
                                    ]
                                ]
                            ]
                        ]
                    ])
                ]);
            }
        }

        return 'ok';
    }
}
