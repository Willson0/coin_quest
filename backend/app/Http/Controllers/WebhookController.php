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

            if (trim($text) === '/start') {
                Telegram::sendPhoto([
                    'chat_id' => $chatId,
                    'caption'    => 'Лучшие крипто курсы',
//                    'parse_mode' => 'MarkdownV2',
                    "photo" => InputFile::create(Storage::disk("public")->path("message.jpg")),
                    "reply_markup" => json_encode([
                        "inline_keyboard" => [
                            [
                                [
                                    "text" => "Открыть веб-приложение",
                                    "web_app" => [
                                        "url" => "https://" . env("DOMAIN") . "?s=home",
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
