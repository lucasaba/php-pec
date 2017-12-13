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
 * Time: 15.14
 */

namespace PhpPec;
use Fetch\Attachment;


/**
 * Interface PecMessageInterface
 *
 * Questa interfaccia definisce i requisiti indispensabili per gestire
 * uno scambio e l'identificazione di messaggi di PEC
 *
 * (PEC: regole tecniche D.M. 2 Nov. 2015)
 *
 * @package PhpPEC\PecMessage
 */
interface PecMessageInterface
{
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
    function realeMittente();

    /**
     * Il messaggio originale è contenuto in un allegato della busta PEC.
     * Questa funzione dovrebbe restituirne il contenuto
     *
     * @param bool $inHtml Se true, il metodo tenta di restituire la versione HTML del messagio
     * @return null|array
     */
    function getTestiOriginali($inHtml = false);

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
    function getAllegati();

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
    function getRicevuta();

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
    function getTipoRicevuta();

    /**
     * Restituisce il messaggio a cui si riferisce una determinata ricevuta
     * In pratica, il campo X-Riferimento-Message-ID della ricevuta.
     * Il campo X-Riferimento-Message-ID si associa al campo Message-Id
     * della mail inviata.
     *
     * @return string|null
     */
    function getIdMessaggioDiRiferimento();

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
    function getTrasporto();
}