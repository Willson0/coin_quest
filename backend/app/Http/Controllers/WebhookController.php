<?php

namespace App\Http\Controllers;

use App\Http\utils;
use App\Jobs\SendPaymentMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class WebhookController extends Controller
{
    public function tg (Request $request) {
        $update = Telegram::getWebhookUpdate();

        if (isset($update['callback_query'])) {
            $requestUser = $request->callback_query["from"];
            $chatId = $requestUser["id"];

            if (isset($request["callback_query"]["data"])) {
                if ($request["callback_query"]["data"] == "get_payment") {
                    utils::answerData("Успешно", $request, $requestUser["id"]);

                    $text = "Способ оплаты: *Оплата на криптокошелёк*.
К оплате: ~650 USDT~ *99 USDT*
Ваш ID: `$chatId`

Реквизиты для оплаты:

*Оплата на криптокошелёк.*

Описание:

Оплата производится стейблкойном (USDT) по адресу кошелька.

Cеть: Tron (TRC20)

Адрес кошелька для пополнения:

`TUR3f5mbHYguyDV8fvyFXSxih68M1Vdpgn`

Для удобства скопируйте это сообщение и удалите весь текст кроме самого номера кошелька.
\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_\\_
_Вы платите физическому лицу._
_Деньги поступят на счёт получателя._";
                    $escape_chars = ['[', ']', '(', ')', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
                    foreach ($escape_chars as $char) {
                        $text = str_replace($char, '\\' . $char, $text);
                    }

                    Telegram::sendPhoto([
                        "chat_id" => $chatId,
                        "photo" => InputFile::create(Storage::disk("public")->path("rep2.jpg")),
                        "caption" => $text,
                        'parse_mode' => 'MarkdownV2',
                        "reply_markup" => json_encode([
                        "inline_keyboard" => [
                            [
                                [
                                    "text" => "Оплачено",
                                    "callback_data" => "payed",
                                ]
                            ]
                        ]])
                    ]);
                }
                if ($request["callback_query"]["data"] == "payed") {
                    utils::answerData("Успешно", $request, $chatId);
                    $text = "*💁🏻‍♂️ Оплатили?*

👌🏻 Тогда `отправьте сюда картинкой (не документом!) квитанцию платежа: скриншот или фото.`

На квитанции должны быть четко видны: *дата, время и сумма платежа. За спам вы можете быть заблокированы!*";

                    $escape_chars = ['[', ']', '(', ')', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
                    foreach ($escape_chars as $char) {
                        $text = str_replace($char, '\\' . $char, $text);
                    }

                    Telegram::sendMessage([
                        "chat_id" => $chatId,
                        "text" => $text,
                        'parse_mode' => 'MarkdownV2',
                        "reply_markup" => json_encode([
                            "inline_keyboard" => [
                                [
                                    [
                                        "text" => "Вопросы и поддержка по доступу",
                                        "url" => "https://t.me/" . env("USERNAME"),
                                    ],
                                ],[
                                    [
                                        "text" => "Назад",
                                        "callback_data" => "get_payment",
                                    ]
                                ]
                            ]])
                    ]);
                }
            }
        }

        if (isset($update['message'])) {
            $message = $update['message'];

            $requestUser = $message["from"];
            $user = User::where("telegram_id", "=", $requestUser["id"])->first();

//            $isFirst = false;
//            if (!$user) {
//                $isFirst = true;
//                $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
//                $address = 'D';
//                for ($i = 0; $i < 33; $i++) $address .= $alphabet[random_int(0, strlen($alphabet)-1)];
//
//                $user = User::create([
//                    "telegram_id" => $requestUser["id"],
//                    "username" => $requestUser["username"] ?? "",
//                    "fullname" => $requestUser["first_name"] ??
//                        $requestUser["last_name"] ?? $requestUser["username"],
//                    "avatar" => $requestUser["photo_url"],
//                    "wallet_private" => bin2hex(random_bytes(32)),
//                    "wallet" => $address,
//                ]);
//            }

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
                    'caption' => $caption,
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
//            } else {
//                $random_int = random_int(1,2);
//                Telegram::sendPhoto([
//                    'chat_id' => $chatId,
//                    'parse_mode' => 'MarkdownV2',
//                    "photo" => InputFile::create(Storage::disk("public")->path("rep$random_int.jpg")),
//                ]);
//            }
        }

        return 'ok';
    }
}
