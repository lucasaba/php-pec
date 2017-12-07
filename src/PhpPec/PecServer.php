<?php
/**
 * Questo file fa parte del progetto php-pec.
 * Il codice è fornito senza alcuna garanzia e distribuito
 * con licenza di tipo open source.
 * Per le informazioni sui diritti e le informazioni sulla licenza
 * consultare il file LICENSE che deve essere distribuito
 * insieme a questo codice.
 *
 * (c) Luca Saba <lucasaba@gmail.com>
 *
 * Created by PhpStorm.
 * User: luca
 * Date: 07/12/17
 * Time: 16.43
 */

namespace PhpPEC\PecMessage;


use Fetch\Server;

/**
 * Class PecServer
 * Estensione della classe Server del pacchetto Fetch
 * per adeguarlo alle esigenze della PEC
 *
 * @package PhpPEC\PecMessage
 */
class PecServer extends Server
{
    public function getMessages($limit = null)
    {
        $numMessages = $this->numMessages();

        if (isset($limit) && is_numeric($limit) && $limit < $numMessages)
            $numMessages = $limit;

        if ($numMessages < 1)
            return array();

        $stream   = $this->getImapStream();
        $messages = array();
        for ($i = 1; $i <= $numMessages; $i++) {
            $uid        = imap_uid($stream, $i);
            $messages[] = new PecMessage($uid, $this);
        }

        return $messages;
    }
}