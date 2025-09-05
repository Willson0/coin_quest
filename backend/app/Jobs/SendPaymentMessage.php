<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendPaymentMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public $chatId;

    public function __construct($chatId)
    {
        $this->chatId = $chatId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $caption = "*BIG SALE*

ТОЛЬКО СЕГОДНЯ! ~650 USDT~ *99 USDT*

Получите доступ и обретите роль «Quester», *с максимальной скидкой -85%*";

        $escape_chars = ['[', ']', '(', ')', '`', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        foreach ($escape_chars as $char) {
            $caption = str_replace($char, '\\' . $char, $caption);
        }

        Telegram::sendPhoto([
            'chat_id' => $this->chatId,
            "photo" => InputFile::create(Storage::disk("public")->path("rep1.jpg")),
            'caption'    => $caption,
            'parse_mode' => 'MarkdownV2',
            "reply_markup" => json_encode([
                "inline_keyboard" => [
                    [
                        [
                            "text" => "Получить доступ 🔓",
                            "callback_data" => "get_payment",
                        ]
                    ]
                ]
            ])
        ]);
    }
}
