## Laravel EPC-QR-Code Generator

Library for generating epc qr codes for sepa payments. See https://de.wikipedia.org/wiki/EPC-QR-Code for more information.

QR-Code generation is provided by https://github.com/endroid/qr-code

If you need a more general solution without the dependencies of endroid and laravel have a look at https://github.com/smhg/sepa-qr-data-php

### Installation

Require this package in your composer.json and update composer. 

    composer require ccharz/laravel-epc-qr

### Usage

With these methods you can set the sepa payment data, all methods can be chained.

* iban(string $iban)
* bic(string $bic)
* accountOwner(string account_owner)
* receiver(string $iban, string $bic, string $account_owner)
* purpose(string $code): 4 Character Purpose Code
* reference(string $reference): Reconciliation reference (Mutually exclusive with text)
* text(string $text): Reconciliation text
* amount(float $amount, string $currency = 'EUR')
* note(string $note): User note

To stream the output directly to the browser use the stream method

```php
return EPCQR::amount(150)
    ->receiver('AT000000000000000000', 'ABCDATWW', 'Max Mustermann')
    ->note('test')
    ->purpose('AT12')
    ->text('Test Überweisungstext')
    ->stream();
```

To only get the endroid/qr-code result use the build method

```php
$result = EPCQR::amount(150)
    ->receiver('AT000000000000000000', 'ABCDATWW', 'Max Mustermann')
    ->note('test')
    ->purpose('AT12')
    ->text('Test Überweisungstext')
    ->build();

// Generate a data URI to include image data inline (i.e. inside an <img> tag)
echo $result->getDataUri();
```

You can use the methods `size(int $size)` and `margin(int $margin)` to adapt the qr code to your needs. If you need more customisation you can also get the endroid/qr-code builder by using the prepareBuilder() method:

```php
$builder = EPCQR::amount(150)
    ->receiver('AT000000000000000000', 'ABCDATWW', 'Max Mustermann')
    ->note('test')
    ->purpose('AT12')
    ->text('Test Überweisungstext')
    ->prepareBuilder();

$result = $builder
    ->labelText('This is the label')
    ->build();
```

If you want to store the output into a file use the save method


```php
return EPCQR::amount(150)
    ->receiver('AT000000000000000000', 'ABCDATWW', 'Max Mustermann')
    ->note('test')
    ->purpose('AT12')
    ->save('qr.png', 'mydisk');
```

### More Information on EPC QR

* https://de.wikipedia.org/wiki/EPC-QR-Code
* https://www.stuzza.at/de/download/qr-code.html

### TODO

* EPC Data Validation

## License

The MIT License (MIT)