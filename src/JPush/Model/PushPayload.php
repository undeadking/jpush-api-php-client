<?php
/**
 * Created by PhpStorm.
 * User: xiezefan
 * Date: 14-6-9
 * Time: 下午2:06
 */

namespace JPush\Model;

use InvalidArgumentException;

class PushPayload {
    private $client;
    private $platform;
    private $audience;
    private $notification;
    private $message;
    private $options;

    function __construct($client)
    {
        $this->client = $client;
    }

    public function setAudience($audience)
    {
        $this->audience = $audience;
        return $this;
    }

    public function setMessage($message)
    {
        if (!is_array($message)) {
            throw new InvalidArgumentException("Invalid Message");
        }
        $this->message = $message;
        return $this;
    }

    public function setNotification($notification)
    {
        if (!is_array($notification)) {
            throw new InvalidArgumentException("Invalid Notification");
        }
        $this->notification = $notification;
        return $this;
    }

    public function setOptions($options)
    {
        if (!is_array($options)) {
            throw new InvalidArgumentException("Invalid Options");
        }
        $this->options = $options;
        return $this;
    }

    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    public function getJSON()
    {
        if (is_null($this->platform) || is_null($this->audience)) {
            throw new InvalidArgumentException("platform and audience must be set");
        }

        if (is_null($this->notification) && is_null($this->message)) {
            throw new InvalidArgumentException("Either or both notification and message must be set.");
        }

        $payload = array(
            'platform' => $this->platform,
            'audience' => $this->audience,
        );

        if (!is_null($this->notification))
        {
            $payload['notification'] = $this->notification;
        }
        if (!is_null($this->message))
        {
            $payload['message'] = $this->message;
        }

        if (!is_null($this->options))
        {
            $payload['options'] = $this->options;
        } else {
            $payload['options'] = options(generateSendno());
        }

        return json_encode($payload);
    }


    public function printJSON() {
        echo $this->getJSON() . '<br/>';
        return $this;
    }

    public function send() {
        $this->validate();
        $response = $this->client->sendPush($this->getJSON());
        return new PushResponse($response);
    }


    /**
     * calculate string length by byte
     * @param $string
     * @return int
     */
    private function calculateLength($string) {
        $bytes = array();
        for($i = 0; $i < strlen($string); $i++){
            $bytes[] = ord($string[$i]);
        }
        return count($bytes);
    }

    /**
     * validate payload legality
     */
    private function validate() {
        $message = $this->message;
        $notification = $this->notification;


        $ios = 0;
        $android = 0;
        $winphone = 0;

        if (!is_null($notification)) {
            $hasAlert = array_key_exists('alert', $notification);
            $alert = "";
            if ($hasAlert) {
                $notification['alert'];
            }
            if (array_key_exists('ios', $notification)) {
                $ios = $this->calculateLength(json_encode($notification['ios']));
            } else if ($hasAlert) {
                $ios = $this->calculateLength(json_encode(array('alert'=>$alert, 'sound'=>'', 'badge'=>1)));
            }

            if ($ios > 220) {
                throw new InvalidArgumentException("ios notification length should be less than 220");
            }

            if (array_key_exists('android', $notification)) {
                $android = $this->calculateLength(json_encode($notification['android']));
            } else if ($hasAlert) {
                $android = $this->calculateLength(json_encode(array('alert'=>$alert)));
            }

            if (array_key_exists('winphone', $notification)) {
                $winphone = $this->calculateLength(json_encode($notification['winphone']));
            } else if ($hasAlert) {
                $winphone = $this->calculateLength(json_encode(array('alert'=>$alert)));
            }

            if (!is_null($message)) {
                $msg_len = $this->calculateLength($message);
                $ios += $msg_len;
                $winphone += $msg_len;
                $android += $msg_len;
            }

            if ($ios > 1200) {
                throw new InvalidArgumentException("ios notification and message length should be less than 1200");
            }
            if ($android > 1200) {
                throw new InvalidArgumentException("android notification and message length should be less than 1200");
            }
            if ($winphone > 1200) {
                throw new InvalidArgumentException("winphone notification and message length should be less than 1200");
            }

        }







    }




} 