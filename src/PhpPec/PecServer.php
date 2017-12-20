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

namespace PhpPec;


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
    /**
     * I messaggi di PEC in ingresso hanno tutti l'oggetto nel formato:
     *
     * POSTA CERTIFICATA: [original subject]
     *
     * Quindi, se voglio solo la posta in ingresso e non le ricevute,
     * mi basta selezionare i soli messaggi con questo particolare soggetto
     *
     * @return array
     */
    public function recuperaPecInIngresso($filtro = 'SUBJECT "POSTA CERTIFICATA: "')
    {
        if ($results = imap_search($this->getImapStream(), $filtro, SE_UID)) {
            if (isset($limit) && count($results) > $limit)
                $results = array_slice($results, 0, $limit);

            $messages = array();

            foreach ($results as $messageId) {
                $messages[] = new PecMessage($messageId, $this);
            }

            return $messages;
        } else {
            return array();
        }
    }

    /**
     * Fa l'overload del metodo padre per restituire una array
     * di oggetti PecMessage invece di Fetch\Message
     *
     * @param null $limit
     * @return array
     */
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

    /**
     * Returns the requested email or false if it is not found.
     *
     * @param  int          $uid
     * @return PecMessage|bool
     */
    public function getMessageByUid($uid)
    {
        try {
            $message = new PecMessage($uid, $this);

            return $message;
        } catch (\Exception $e) {
            return false;
        }
    }
}