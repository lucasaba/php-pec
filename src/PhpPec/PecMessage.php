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
 * Time: 15.10
 */

namespace PhpPec;

use Fetch\Attachment;
use Fetch\Message;
use Fetch\Server;
use PhpPec\Parser\PostacertParser;

/**
 * Class PecMessage
 *
 * La classe estende un normale messaggio di posta elettronica (nella fattispecie Fetch\Message)
 * aggiungendovi le funzioni tipicamente relative al messaggio PEC
 *
 *
 * @package PhpPEC\PecMessage
 */
class PecMessage extends Message implements PecMessageInterface
{
    private $allegatiDiServizio = ['daticert.xml', 'smime.p7s', 'postacert.eml'];

    /**
     * @var string|null
     */
    private $ricevuta;

    /**
     * @var string|null
     */
    private $tipoRicevuta;

    /**
     * @var string|null
     */
    private $idMessaggioDiRiferimento;

    /**
     * @var string|null
     */
    private $trasporto;

    private $rawBody;

    public function __construct($messageUniqueId, Server $connection)
    {
        parent::__construct($messageUniqueId, $connection);
        $this->rawBody = null;
        $rawHeaders = $this->getRawHeaders();

        /**
         * Estraggo il campo X-Ricevuta
         */
        $regex = '/X-Ricevuta: (non-accettazione|accettazione|preavviso-errore-consegna|presa-in-carico|rilevazione-virus|errore-consegna|avvenuta-consegna)/';
        if(preg_match($regex, $rawHeaders, $match) > 0) {
            $this->ricevuta = $match[1];
        }

        /**
         * Estraggo il campo X-TipoRicevuta
         */
        $regex = '/X-TipoRicevuta: (completa|breve|sintetica)/';
        if(preg_match($regex, $rawHeaders, $match) > 0) {
            $this->tipoRicevuta = $match[1];
        }

        /**
         * Estraggo il campo X-Trasporto
         */
        $regex = '/X-Trasporto: (posta-certificata|errore)/';
        if(preg_match($regex, $rawHeaders, $match) > 0) {
            $this->trasporto = $match[1];
        }

        /**
         * Estraggo il campo X-Riferimento-Message-ID
         */
        $regex = '/X-Riferimento-Message-ID: (<\S+>)/';
        if(preg_match($regex, $rawHeaders, $match) > 0) {
            $this->idMessaggioDiRiferimento = $match[1];
        }
    }


    public function getOggetto()
    {
        return str_replace('POSTA CERTIFICATA: ', '', $this->getSubject());
    }

    /**
     * Quando si riceve una PEC, il campo `from` è del tipo:
     *
     * From: "Per conto di: mario.bianchi@dominio.it" <posta-certificata@gestore.it>
     *
     * L'indirizzo mail del vero mittente è quindi mario.bianchi@dominio.it
     * che dovrebbe essere anche contenuto in `Return-Path`
     *
     * Questa funzione deve restituire il reale mittente della mail
     *
     * @return array|string|bool
     */
    function realeMittente()
    {
        $mittente = $this->getAddresses('from');
        /**
         * Se si tratta di una pec in ingresso,
         * devo trattare il campo from come sopra descritto
         */
        if($this->getTrasporto()) {
            if(is_array($mittente) && count($mittente) > 0) {
                preg_match('/Per conto di: ([\w-.]+@[\w.-]+)/', $mittente['name'], $match);
                if(count($match) == 2) {
                    return $match[1];
                }
                return $mittente['name'];
            }
        } else {
            /**
             * Si tratta di una ricevuta. Quindi il campo from è un campo regolare con
             * i dati del provider PEC
             */
            return $mittente['address'];
        }

        return false;
    }

    /**
     * Il messaggio originale è contenuto in un allegato della busta PEC.
     * Questa funzione dovrebbe restituirne il contenuto.
     * Gli allegati sono contenuti direttamente nella busta, quindi non
     * è necessario estrarli dal messaggio originale
     *
     * @param bool $inHtml Se true, il metodo tenta di restituire la versione HTML del messagio
     * @return null|array
     */
    function getTestiOriginali($inHtml = false)
    {
        $postacert = $this->getAttachments('postacert.eml');
        /* @var Attachment $postacert */
        $parser = new PostacertParser($postacert->getData());
        return $parser->getFragments();
    }

    /**
     * Restituisce gli allegati originali
     * Non restiruisce gli allegati che hanno a che fare con la PEC tipo:
     *
     * - la firma del messaggi
     * - il file xml daticert
     * - il file con la mail originale postacert.eml
     *
     * @return array|bool|Attachment[]
     */
    function getAllegati()
    {
        $allegati = array();
        foreach ($this->getAttachments() as $attachment) {
            if(! in_array($attachment->getFileName(), $this->allegatiDiServizio)) {
                $allegati[] = $attachment;
            }
        }

        return $allegati;
    }

    /**
     * Restituisce il tipo ricevuta
     * Il tipo ricevuta può essere:
     *
     * - accettazione
     * - non-accettazione
     * - presa-in-carico
     * - avvenuta-consegna
     * - posta-certificata
     * - errore-consegna
     * - preavviso-errore-consegna
     * - rilevazione-virus
     *
     * @return string|null
     */
    function getRicevuta()
    {
        return $this->ricevuta;
    }

    /**
     * Restituisce il tipo di ricevuta richiesta dal messaggio
     * I posibili valori sono:
     *
     * - breve
     * - sintetica
     * - completa
     *
     * @return string|null
     */
    function getTipoRicevuta()
    {
        return $this->tipoRicevuta;
    }

    /**
     * Restituisce il messaggio a cui si riferisce una determinata ricevuta
     * In pratica, il campo X-Riferimento-Message-ID della ricevuta.
     * Il campo X-Riferimento-Message-ID si associa al campo Message-Id
     * della mail inviata.
     *
     * @return string|null
     */
    function getIdMessaggioDiRiferimento()
    {
        return $this->idMessaggioDiRiferimento;
    }

    /**
     * @return \DateTime
     */
    public function getDataMessaggio()
    {
        $data = new \DateTime();
        date_timestamp_set($data, $this->getDate());
        return $data;
    }

    /**
     * Restituisce il trasporto della busta pec che contiene il messaggio
     * originale.
     *
     * In pratica il campo X-Trasporto
     *
     * I possibili valori sono:
     *
     * - posta-certificata
     * - errore
     *
     * @return string|null
     */
    function getTrasporto()
    {
        return $this->trasporto;
    }

    /**
     * Verifica la firma della PEC
     *
     * @return boolean
     */
    function verificaPec()
    {
        $nonce = md5(time().rand(10000, 99999));
        $msg = $this->getRawBody();

        file_put_contents("/tmp/pec-message.$nonce", $msg);

        $result = openssl_pkcs7_verify("/tmp/pec-message.$nonce", 0);

        unlink("/tmp/pec-message.$nonce");
        return $result;
    }

    function getIdMessaggio()
    {
        $headers = $this->getHeaders();
        if(isset($headers->message_id)) {
            return $headers->message_id;
        }
        return null;
    }

    /**
     * @return string
     */
    function getRawBody()
    {
        if($this->rawBody == null) {
            $this->rawBody = imap_fetchbody($this->imapStream, $this->uid, '', FT_UID | FT_PEEK);
        }
        return $this->rawBody;
    }

    /**
     * La funzione originale non tiene conto del problema delle email che contengono
     * altre email.
     *
     * Allo stato attuale la soluzione è già stata proposta (https://github.com/tedious/Fetch/pull/201)
     * ma non ancora mergiata.
     *
     * This function takes in a structure and identifier and processes that part of the message. If that portion of the
     * message has its own subparts, those are recursively processed using this function.
     *
     * @param \stdClass $structure
     * @param string    $partIdentifier
     */
    protected function processStructure($structure, $partIdentifier = null)
    {
        $parameters = self::getParametersFromStructure($structure);

        if ((isset($parameters['name']) || isset($parameters['filename']))
            || (isset($structure->subtype) && strtolower($structure->subtype) == 'rfc822')
        ) {
            $attachment          = new Attachment($this, $structure, $partIdentifier);
            $this->attachments[] = $attachment;
        } elseif ($structure->type == 0 || $structure->type == 1) {
            $messageBody = isset($partIdentifier) ?
                imap_fetchbody($this->imapStream, $this->uid, $partIdentifier, FT_UID | FT_PEEK)
                : imap_body($this->imapStream, $this->uid, FT_UID | FT_PEEK);

            $messageBody = self::decode($messageBody, $structure->encoding);

            if (!empty($parameters['charset']) && $parameters['charset'] !== self::$charset) {
                $mb_converted = false;
                if (function_exists('mb_convert_encoding')) {
                    if (!in_array($parameters['charset'], mb_list_encodings())) {
                        if ($structure->encoding === 0) {
                            $parameters['charset'] = 'US-ASCII';
                        } else {
                            $parameters['charset'] = 'UTF-8';
                        }
                    }

                    $messageBody = @mb_convert_encoding($messageBody, self::$charset, $parameters['charset']);
                    $mb_converted = true;
                }
                if (!$mb_converted) {
                    $messageBodyConv = @iconv($parameters['charset'], self::$charset . self::$charsetFlag, $messageBody);

                    if ($messageBodyConv !== false) {
                        $messageBody = $messageBodyConv;
                    }
                }
            }

            if (strtolower($structure->subtype) === 'plain' || ($structure->type == 1 && strtolower($structure->subtype) !== 'alternative')) {
                if (isset($this->plaintextMessage)) {
                    $this->plaintextMessage .= PHP_EOL . PHP_EOL;
                } else {
                    $this->plaintextMessage = '';
                }

                $this->plaintextMessage .= trim($messageBody);
            } elseif (strtolower($structure->subtype) === 'html') {
                if (isset($this->htmlMessage)) {
                    $this->htmlMessage .= '<br><br>';
                } else {
                    $this->htmlMessage = '';
                }

                $this->htmlMessage .= $messageBody;
            }
        }

        if (! empty($structure->parts)) {
            if (isset($structure->subtype) && strtolower($structure->subtype) === 'rfc822') {
                $this->processStructure($structure->parts[0], $partIdentifier);
            } else {
                // multipart: iterate through each part
                foreach ($structure->parts as $partIndex => $part) {
                    $partId = $partIndex + 1;
                    if (isset($partIdentifier))
                        $partId = $partIdentifier . '.' . $partId;
                    $this->processStructure($part, $partId);
                }
            }
        }
    }
}