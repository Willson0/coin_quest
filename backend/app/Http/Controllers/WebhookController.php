<?php

namespace App\Http\Controllers;

use App\Jobs\SendPaymentMessage;
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

            $caption = "*Добро пожаловать в CoinQuest! 🤖*

>Ваш личный помощник внутри Telegram, который превращает хаос трейдинга в чёткую систему!🎓

Что вас ждет уже сегодня:

*VIP-сигналы* — _повторяйте сделки профессионалов и получайте прибыль._

*ИИ-ассистент 24/7* — _индивидуальный помощник, который поможет принять верное решение в любое время суток!_

*Трейдинг-турниры* — _прокачивайте навыки и соревнуйтесь в реальном времени. (уже в сентябре!)_

*Обучение* — _сложная информация в легкой подаче. Для любого IQ._

*Живой чат участников* — _сообщество единомышленников и постоянная поддержка._

*Идеи от топов рынка* — _сетапы и инсайты от практиков + возможность общаться с профессионалами напрямую._

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
                                    "text" => "Вопросы и поддержка по доступу",
                                    "url" => "https://t.me/" . env("USERNAME"),
                                ]
                            ]
                        ]
                    ])
                ]);
                SendPaymentMessage::dispatch($chatId)->delay(now()->addSeconds(30));
            }
        }

        return 'ok';
    }
}
