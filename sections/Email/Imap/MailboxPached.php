<?php

namespace Iris\Config\CRM\sections\Email\Imap;

use PhpImap\Mailbox;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;
// use PhpImap\Exception;
// use PhpImap\ConnectionException;

class MailboxPached extends Mailbox {
    // http://www.fileformat.info/info/unicode/char/fffd/index.htm
    const REPLACEMENT_CHARACTER_CHAR = 0xFFFD;
    const REPLACEMENT_CHARACTER_STRING = "\xef\xbf\xbd";


    protected function stringToImapString($string)
    {
        return $this->convertStringEncoding($string, "UTF-8", "UTF7-IMAP");
    }

    protected function imapStringToString($imapString)
    {
        // string, fromEncoding, toEncoding
        return $this->convertStringEncoding($imapString, "UTF7-IMAP", "UTF-8");
    }

    /**
     * Switch mailbox without opening a new connection
     *
     * @param string $imapPath
     * @throws Exception
     */
    public function switchMailbox($imapPath = "") {
        $this->imapPath = $imapPath;
        // use extended method
        $this->imap('reopen', $this->imapPath,false);
    }

    /**
     * Get folders list
     * @param string $search
     * @return array
     */
    public function getMailboxes($search = "*") {
        $arr = [];
        if($t = imap_getmailboxes($this->getImapStream(),
            $this->stringToImapString($this->imapPath), $search))
        {
            foreach($t as $item) {
                $arr[] = [
                    "fullpath" =>  $this->imapStringToString($item->name),
                    "attributes" => $item->attributes,
                    "delimiter" => $item->delimiter,
                    "shortpath" =>  $this->imapStringToString(
                        substr($item->name, strpos($item->name, '}') + 1)),
                ];
            }
        }
        return $arr;
    }

    /**
     * Gets listing the folders
     *
     * This function returns an object containing listing the folders.
     * The object has the following properties: messages, recent, unseen, uidnext, and uidvalidity.
     *
     * @param string $pattern
     * @return array listing the folders
     */
    public function getListingFolders($pattern = '*') {
        $folders = $this->imap('list', [$this->imapPath, $pattern]) ?: [];
        foreach($folders as &$folder) {
            // $folder = imap_utf7_decode($folder);
            $folder = $this->imapStringToString($folder);
        }
        return $folders;
    }

    /**
     * Replace chars that not presented in current charset with special replacement character symbol
     *
     */
    public function replaceInvalidByteSequence($string)
    {
        $encoding = $this->serverEncoding;
        $result = "";
        $currentCharacter = mb_substitute_character();

        mb_substitute_character(self::REPLACEMENT_CHARACTER_CHAR);
        $result = mb_convert_encoding($string, $encoding, $encoding);
        mb_substitute_character($currentCharacter); // restore current value

        return $result;
    }

    public function convertLineEndingsToBR($string)
    {
        return nl2br($string);
    }

    /**
     * Safe decode mime string
     *
     * Some mime strings consisting of several parts
     * ("=?utf-8?B?0JLQ...L3Q?= =?utf-8?B?uN..") after decode has invalid
     * character on mime string "split" position. If after decode we have
     * unsupported chars then glue mime string and decode again.
     * Always glue mime string before decoding is NOT silver bullet solution
     * and breaks decode for some strings, so we glue only then default decode
     * has unsupported chars. See more:
     * See https://github.com/mailwatch/MailWatch/issues/630#issuecomment-285952732
     */
    public function decodeMimeStrSafe($string) {
        $toCharset = $this->serverEncoding;
        $result = "";

        $orig_character = mb_substitute_character();
        mb_substitute_character(self::REPLACEMENT_CHARACTER_CHAR);
        $result = $this->decodeMimeStr($string, $toCharset);
        mb_substitute_character($orig_character);

        if ($this->isStringHasInvalidCharacters($result)) {
            $result = $this->decodeMimeStr(
                $this->MimeStrToSingleLine($string), $toCharset);
        }

        return $result;
    }

    protected function isStringHasInvalidCharacters($string) {
        return strpos($string, self::REPLACEMENT_CHARACTER_STRING) !== false;
    }

    protected function MimeStrToSingleLine($string) {
        if (!$this->isAllMimePartsHaveSameCharset($string)) {
            return $string;
        }

        $items = explode("?", $string);
        // =?utf-8?B?0JLQsN... -> =?utf-8?B?
        $mimeStart = "?= ".implode("?", [$items[0], $items[1], $items[2]])."?";

        // "..3Q?= =?utf-8?B?uN.." -> "..3QuN.."
        return str_replace($mimeStart, "", $string);
    }

    protected function isAllMimePartsHaveSameCharset($string) {
        $parts = imap_mime_header_decode($string);
        if ($parts === 1) {
            return false;
        }

        $firstPartCharset = $parts[0]->charset;
        foreach ($parts as $part) {
            if ($part->charset !== $firstPartCharset) {
                return false;
            }
        }

        return true;
    }

    /**
     * Call IMAP extension function call wrapped with utf7 args conversion & errors handling
     *
     * @param $methodShortName
     * @param array|string $args
     * @param bool $prependConnectionAsFirstArg
     * @param string|null $throwExceptionClass
     * @return mixed
     * @throws Exception
     */
    public function imap($methodShortName, $args = [], $prependConnectionAsFirstArg = true, $throwExceptionClass = Exception::class) {
        if(!is_array($args)) {
            $args = [$args];
        }
        $qq = [];
        foreach($args as &$arg) {
            if(is_string($arg)) {
                // $arg = imap_utf7_encode($arg);
                $qq[] = $arg;
                $arg = $this->stringToImapString($arg);
                $qq[] = $arg;
            }
        }
        if($prependConnectionAsFirstArg) {
            array_unshift($args, $this->getImapStream());
        }

        imap_errors(); // flush errors
        $result = @call_user_func_array("imap_$methodShortName", $args);

        if(!$result) {
            $errors = imap_errors();
            if($errors) {
                if($throwExceptionClass) {
                    // throw new $throwExceptionClass("IMAP method imap_$methodShortName() failed with error: " . implode('. ', $errors));
                    throw new $throwExceptionClass("IMAP method " . implode('. ', $qq));
                }
                else {
                    return false;
                }
            }
        }

        return $result;
    }
}

class Exception extends \Exception {

}

class ConnectionException extends \Exception {

}
