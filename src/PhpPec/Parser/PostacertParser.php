<?php
/**
 * Questo file fa parte del progetto epod4.
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
 * Date: 13/12/17
 * Time: 10.21
 */

namespace PhpPec\Parser;

/**
 * Class PostacertParser
 * Effettua il parsing di postacert.eml
 * Quello che ci serve è il testo della mail originale.
 * @package PhpPec\Parser
 */
class PostacertParser
{
    /**
     * La regular expression per il boundary di una multipart mime mail
     */
    const MULTIPART_REGEX='/Content-Type: multipart\/(mixed|alternative);\s+boundary="([\w\\\'\(\)\+,\.\-@\=\/?]+)"/';

    private $sevenBitChars = [
        '=20', // space.
        '=E2=80=99', // single quote.
        '=0A', // line break.
        '=A0', // non-breaking space.
        '=C2=A0', // non-breaking space.
        "=\r\n", // joined line.
        '=E2=80=A6', // ellipsis.
        '=E2=80=A2', // bullet.
    ];

    private $sevenBitSubstitute = [
        ' ',
        "'",
        "\r\n",
        ' ',
        ' ',
        '',
        '…',
        '•'
    ];

    /**
     * Il contenuto di postacert.eml
     * @var string
     */
    private $content;

    /**
     * @var array
     */
    private $boundaries;

    /**
     * @var array
     */
    private $types;

    /**
     * @var array
     */
    private $fragments;

    public function __construct($content)
    {
        $this->content = $content;
        $this->boundaries = array();
        $this->types = array();
        $this->fragments = array();
        $this->parse();
    }

    /**
     * The body must then contain
     * one or more body parts, each preceded by a boundary delimiter line,
     * and the last one followed by a closing boundary delimiter line.
     * After its boundary delimiter line, each body part then consists of a
     * header area, a blank line, and a body area.
     * --------------------------------------------------------------------
     * The boundary delimiter line is then defined as a line
     * consisting entirely of two hyphen characters ("-", decimal value 45)
     * followed by the boundary parameter value from the Content-Type header
     * field, optional linear whitespace, and a terminating CRLF.
     * --------------------------------------------------------------------
     * boundary := 0*69<bchars> bcharsnospace
     *      bchars := bcharsnospace / " "
     *      bcharsnospace := DIGIT / ALPHA / "'" / "(" / ")" /
     *                       "+" / "_" / "," / "-" / "." /
     *                       "/" / ":" / "=" / "?"
     */
    private function parse()
    {
        preg_match_all($this::MULTIPART_REGEX, $this->content, $matches);
        /**
         * $matches dovrebbe essere un array composto da 3 elementi
         * $matches[0] contiene tutte le stringhe che combaciano con la regex
         * $matches[1] contiene tutti i tipo di multipart
         * $matches[2] contiene tutti i boundary
         */
        if(count($matches) != 3) {
            return;
        }
        for($i = 0; $i < count($matches[0]); $i++) {
            $this->boundaries[] = '--'.$matches[2][$i];
            $this->types[]      = $matches[1][$i];
        }

        /**
         * Leggo postacert.eml riga per riga alla ricerca dei vari frammenti
         */
        $numeroFrammento = 0;
        $partiMessaggio = array();
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $this->content) as $line){
            if(in_array(trim($line), $this->boundaries)) {
                $numeroFrammento++;
                continue;
            }
            if(isset($partiMessaggio[$numeroFrammento])) {
                $partiMessaggio[$numeroFrammento] .= $line."\n";
            } else {
                $partiMessaggio[$numeroFrammento] = $line."\n";
            }
        }

        /**
         * A questo punto mi servono i frammenti il cui content type sia
         * text/plain o text/html
         */
        $this->fragments = array();
        foreach ($partiMessaggio as $parteMessaggio) {
            if(preg_match('/Content-Type: text\/(html|plain)/', $parteMessaggio, $matches)) {
                // Ripulisco il frammento dall'eventuale finale di parte
                foreach ($this->boundaries as $boundary) {
                    $parteMessaggio = str_replace("$boundary--\n", '', $parteMessaggio);
                }

                // Gestisco l'eventuale encoding del contenuto:
                preg_match('/Content-Transfer-Encoding: (\w+)/', $parteMessaggio, $encoding);
                if(count($encoding) > 0) {
                    // Elimino l'intestazione della parte del messaggio
                    $parteMessaggio = trim(preg_replace('/Content[^\n]*/', '', $parteMessaggio));
                    switch (strtolower($encoding[1])) {
                        case 'base64':
                            $parteMessaggio = base64_decode($parteMessaggio);
                            break;
                        case '7bit':
                            //$parteMessaggio = str_replace($this->sevenBitChars, $this->sevenBitSubstitute, $parteMessaggio);
                            $parteMessaggio = mb_convert_encoding($parteMessaggio, 'UTF-8', 'UTF-7');
                            break;
                        case '8bit':
                            $parteMessaggio = imap_8bit($parteMessaggio);
                            break;
                        case 'quoted':
                            $parteMessaggio = utf8_encode(quoted_printable_decode($parteMessaggio));
                            break;
                        default:
                            $parteMessaggio .= strtolower($encoding[1]);
                    }
                }

                $this->fragments[] = [
                    'contenuto' => $parteMessaggio,
                    'tipo' => $matches[1]
                ];
            }
        }
    }

    public function getFragments()
    {
        return $this->fragments;
    }
}
