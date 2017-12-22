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
