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

    public function __construct($messageUniqueId, Server $connection)
    {
        parent::__construct($messageUniqueId, $connection);
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
     * @param bool $formatoString
     * @return array|string|bool
     */
    function realeMittente($formatoString = false)
    {
        // TODO: Implement realeMittente() method.
    }

    /**
     * Il messaggio originale è contenuto in un allegato della busta PEC.
     * Questa funzione dovrebbe restituirne il contenuto
     *
     * @param bool $inHtml Se true, il metodo tenta di restituire la versione HTML del messagio
     * @return null|string
     */
    function getTestoOriginale($inHtml = false)
    {
        // TODO: Implement getTestoOriginale() method.
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
        // TODO: Implement getAllegati() method.
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
}