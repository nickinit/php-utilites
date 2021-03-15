<?php


class Telegram
{
    const BOT_TOKEN = 'bot_token';
    const SECRET_GROUP_ID = 'chat_id_for_group';
    const HOST = 'https://host/path/to/script';

    /**
     * @var array $wh Webhook as associative array
     * @var integer $chat_id
     * @var string $text Text of message
     * @var integer @message_id ID of forwarded message
     *
     */

    public $wh;
    public $chat_id;
    public $text;
    public $message_id;
    public $original_message_id;
    public $original_chat_id;
    public $callback_query_id;

    /**
     * Telegram constructor.
     */
    public function __construct()
    {
        $body = file_get_contents('php://input');
        $webhook = json_decode($body, true);

        self::logThis(print_r($webhook, true), "\r\nConstructor, webhook ------------------------\r\n");

        $this->wh = $webhook;

        $this->chat_id = $webhook['message']['chat']['id'];
        $this->text = $webhook['message']['text'];
        $this->message_id = $webhook['message']['message_id'];

        if ($webhook['callback_query']) {
            $this->callback_query_id = $webhook['callback_query']['id'];
        }
        if (isset($webhook['message']['reply_to_message'])) {
            $this->original_message_id = $webhook['message']['reply_to_message']['message_id'];
            $this->original_chat_id = $webhook['message']['reply_to_message']['forward_from']['id'];
        }

        self::logThis(print_r($this, true), "\r\nConstructor, MODEL!!!!!---------------------\r\n");


    }

    /**
     * @param string $chat_id CHAT ID where the message is forwarded
     * @param string $token Bot token
     * @return bool
     */
    public function forwardMessage($chat_id = self::SECRET_GROUP_ID,  $token = self::BOT_TOKEN)
    {

        $request = [
            'from_chat_id' => $this->chat_id, // FROM
            'chat_id' => $chat_id, // TO
            'message_id' => $this->message_id,
        ];

        $request = json_encode($request);

        return (self::sendSomething($request, 'forwardMessage') ?
            true : false);

    }

    /**
     * @param string $token Bot token
     * @return string
     */
    public function getWebhookInfo($token=self::BOT_TOKEN)
    {
        $url = "https://api.telegram.org/bot{$token}/getWebhookInfo";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($result, true);

        return ($response['ok'] ? 'Good!':
            "Err#{$response['error_code']}: {$response['description']}");
    }

    /**
     * Processes messages in defined group. If message is "reply"
     * and exists message_id from original chat (e.g. 'to212. Please reboot system.')
     * then user will give pretty answer in form of an "reply".
     * @return bool
     */
    public function forwardReplyFromGroup()
    {
        if ($this->chat_id == self::SECRET_GROUP_ID &&
            isset($this->wh['message']['reply_to_message'])) {
            preg_match('#to(\d+)\.(.*)#', $this->text, $matches);

            self::sendMessage(
                $matches ? $matches[2] : $this->text,
                $this->original_chat_id,
                $matches ? $matches[1] : $this->original_message_id
            );
            return true;

        }
        return false;

    }

    /**
     * Sends curl request with POST fields
     * @param $request
     * @param string $type of request. E.g. sendMessage, forwardMessage
     * @param string $token
     * @return bool
     */
    public static function sendSomething($request, $type, $token = self::BOT_TOKEN)
    {
        $ch = curl_init("https://api.telegram.org/bot{$token}/{$type}");

        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        $result = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($result, true);
        self::logThis(print_r($response, true),"\r\nsendSomething, response----------------------\r\n" );
        return ($response['ok'] ? true : false);

    }

    public static function sendMessage($text, $chat_id, $reply_to_message_id = false, $token = self::BOT_TOKEN)
    {
        $request = [
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_to_message_id' => $reply_to_message_id,
        ];
        $request = json_encode($request);

        self::sendSomething($request, 'sendMessage');
    }

    /**
     * My mini logger ;)
     * @param $content
     * @param string $head
     * @param string $file
     * @param bool $rewrite
     */
    public static function logThis($content, $head = "\r\n----------------\r\n", $file = 'runtime/log.txt', $rewrite = false)
    {
        file_put_contents($file, $head . $content . "\r\n" . time(), FILE_APPEND);
    }
  
    // Keyboard tests
    $text = 'keyboard';
  
    /* Keyboard from https://core.telegram.org/bots/api#replykeyboardmarkup */
    $buttons = [
        ['btn1', 'btn2', 'btn3'],
        ['btn1', 'btn1', 'btn1'],
        ['btn1', [
            'text' => 'give me your phone',
            'request_contact' => true,
            ]
        ],
    
    ];
    $keyboard = [
        'keyboard' => $buttons,
        'resize_keyboard' => true,
    //            'one_time_keyboard' => true,
    ];
    
    
    /* Keyboard from https://core.telegram.org/bots/api#inlinekeyboardmarkup */
    $inline_buttons = [
       [ ['text' => 'v1', 'callback_data' => 'test1'],
        ['text' => 'v2', 'callback_data' => 'test2'],
        ['text' => 'v3', 'callback_data' => 'test3'],],
    ];
    
    $keyboard = [
        'inline_keyboard' => $inline_buttons,
    ];
  
    $r = [
        'chat_id' => $this->chat_id,
        'text' => $text,
        'reply_markup' => $keyboard,
    
    ];
    
    // Precessing of query https://core.telegram.org/bots/api#answercallbackquery
    
    if ($model->callback_query_id) {
      
        $type = 'answerCallbackQuery';
      
        $data = [
            'callback_query_id' => $model->callback_query_id,
            'text' => 'hellohello',
            'show_alert' => true, // If false, answer is notification 
        ];
      
        $data = json_encode($data);
      
        Telegram::sendSomething($data,$type);
      
        return 1;
    }
    
    
    $r = json_encode($r);
    self::sendSomething($r, 'sendMessage');
  

    
}

?>
