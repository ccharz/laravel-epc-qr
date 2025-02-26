<?php

namespace Ccharz\LaravelEpcQr;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Response;
use InvalidArgumentException;

class LaravelEpcQr
{
    public const ENCODING_UTF_8 = 1;
    public const ENCODING_ISO8859_1 = 2;
    public const ENCODING_ISO8859_2 = 3;
    public const ENCODING_ISO8859_4 = 4;
    public const ENCODING_ISO8859_5 = 5;
    public const ENCODING_ISO8859_7 = 6;
    public const ENCODING_ISO8859_10 = 7;
    public const ENCODING_ISO8859_15 = 8;

    /**
     * @var FilesystemManager
     */
    protected FilesystemManager $filesystemManager;

    /**
     * @var string
     */
    protected string $receiver_bank_bic = '';

    /**
     * @var string
     */
    protected string $receiver_account_owner = '';

    /**
     * @var string
     */
    protected string $receiver_account_iban = '';

    /**
     * @var string
     */
    protected string $currency = 'EUR';

    /**
     * @var float
     */
    protected float $amount = 0;

    /**
     * @var string
     */
    protected string $purpose_code = '';

    /**
     * @var string
     */
    protected string $reconciliation_reference = '';

    /**
     * @var string
     */
    protected string $reconciliation_text = '';

    /**
     * @var string
     */
    protected string $user_note = '';

    /**
     * Size of the QR Code
     *
     * @var int
     */
    protected int $size = 300;

    /**
     * Margin of the QR Code
     *
     * @var int
     */
    protected int $margin = 10;

    /**
     * @var string
     */
    protected string $line_seperator = "\n";

    /**
     * @var int
     */
    protected int $encoding = self::ENCODING_ISO8859_15;

    /**
     * @var string
     */
    protected string $image_format = 'png';

    /**
     * @var array<int, string>
     */
    protected array $encodings = [
        self::ENCODING_UTF_8 => 'UTF-8',
        self::ENCODING_ISO8859_1 => 'ISO-8859-1',
        self::ENCODING_ISO8859_2 => 'ISO-8859-2',
        self::ENCODING_ISO8859_4 => 'ISO-8859-4',
        self::ENCODING_ISO8859_5 => 'ISO-8859-5',
        self::ENCODING_ISO8859_7 => 'ISO-8859-7',
        self::ENCODING_ISO8859_10 => 'ISO-8859-10',
        self::ENCODING_ISO8859_15 => 'ISO-8859-15',
    ];

    /**
     * @param FilesystemManager $filesystemManager
     *
     * @return void
     */
    public function __construct(FilesystemManager $filesystemManager)
    {
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * @param float $amount
     * @param string $currency
     *
     * @return static
     */
    public function amount(float $amount, string $currency = 'EUR'): self
    {
        $this->amount = $amount;

        $this->currency = $currency;

        return $this;
    }

    /**
     * @param string $iban
     * @param string $bic
     * @param string $account_owner
     *
     * @return static
     */
    public function receiver(string $iban, string $bic, string $account_owner): self
    {
        return $this
            ->iban($iban)
            ->bic($bic)
            ->accountOwner($account_owner);
    }

    /**
     * @param string $iban
     *
     * @return static
     */
    public function iban(string $iban): self
    {
        $this->receiver_account_iban = $iban;

        return $this;
    }

    /**
     * @param string $bic
     *
     * @return static
     */
    public function bic(string $bic): self
    {
        $this->receiver_bank_bic = $bic;

        return $this;
    }

    /**
     * @param string $account_owner
     *
     * @return static
     */
    public function accountOwner(string $account_owner): self
    {
        $this->receiver_account_owner = $account_owner;

        return $this;
    }

    /**
     * @param string $code 4-Character purpose code
     *
     * @return static
     */
    public function purpose(string $code): self
    {
        $this->purpose_code = $code;

        return $this;
    }

    /**
     * Sets the reconciliation reference.
     *
     * @param string $reference Reconciliation reference (35-Bytes)
     *
     * @return static
     */
    public function reference(string $reference): self
    {
        $this->reconciliation_reference = $reference;

        $this->reconciliation_text = '';

        return $this;
    }

    /**
     * Sets the reconciliation text.
     *
     * @param string $text Reconciliation text (140 Characters)
     *
     * @return static
     */
    public function text(string $text): self
    {
        $this->reconciliation_text = $text;

        $this->reconciliation_reference = '';

        return $this;
    }

    /**
     * Add a user note / additional message
     *
     * @param string $note
     *
     * @return static
     */
    public function note(string $note): self
    {
        $this->user_note = $note;

        return $this;
    }

    /**
     * @param int $size
     *
     * @return static
     */
    public function size(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @param int $encoding
     *
     * @return static
     *
     * @throws InvalidArgumentException
     */
    public function encoding(int $encoding): self
    {
        if (!isset($this->encodings[$encoding])) {
            throw new InvalidArgumentException('Invalid encoding selected');
        }

        $this->encoding = $encoding;

        return $this;
    }

    /**
     * @param int $margin
     *
     * @return self
     */
    public function margin(int $margin): self
    {
        $this->margin = $margin;

        return $this;
    }

    /**
     * @param string $image_format
     *
     * @return self
     */
    public function imageFormat(string $image_format): self
    {
        $this->image_format = $image_format;

        return $this;
    }

    /**
     * @return string
     */
    public function output(): string
    {
        $output = [
            'BCD',
            '002',
            $this->encoding,
            'SCT',
            $this->receiver_bank_bic ?? '',
            $this->receiver_account_owner ?? '',
            $this->receiver_account_iban ?? '',
            $this->amount && $this->currency
                ? $this->currency . number_format($this->amount, 2, '.', '')
                : '',
            $this->purpose_code ?? '',
            $this->reconciliation_reference ?? '',
            $this->reconciliation_text ?? '',
            $this->user_note ?? '',
        ];

        return implode(
            $this->line_seperator,
            $output
        );
    }

    /**
     * @return \Endroid\QrCode\Builder\BuilderInterface
     */
    public function prepareBuilder(): BuilderInterface
    {
        $builder = Builder::create()
            ->encoding(
                new Encoding($this->encodings[$this->encoding])
            )
            ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->data($this->output())
            ->size($this->size)
            ->margin($this->margin);

        switch ($this->image_format) {
            case 'svg':
                $builder->writer(new SvgWriter());
                break;
        }

        return $builder;
    }

    /**
     * @return \Endroid\QrCode\Writer\Result\ResultInterface
     */
    public function build(): ResultInterface
    {
        $builder = $this->prepareBuilder();

        return $builder->build();
    }

    /**
     * Return a response with the PDF to show in the browser
     *
     * @return \Illuminate\Http\Response
     */
    public function stream(): Response
    {
        $result =  $this->build();

        return new Response(
            $result->getString(),
            200,
            ['Content-Type' => $result->getMimeType()]
        );
    }

    /**
     * @param string $filename
     * @param string|null $disk
     *
     * @return bool
     */
    public function save(string $filename = 'qr.png', ?string $disk = null): bool
    {
        return $this->filesystemManager->disk($disk)->put(
            $filename,
            $this->build()->getString()
        );
    }
}
