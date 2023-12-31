# Very short description of the package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/evance-odhiambo/mpesa-payment.svg?style=flat-square)](https://packagist.org/packages/evance-odhiambo/mpesa-payment)
[![Total Downloads](https://img.shields.io/packagist/dt/evance-odhiambo/mpesa-payment.svg?style=flat-square)](https://packagist.org/packages/evance-odhiambo/mpesa-payment)
![GitHub Actions](https://github.com/evance-odhiambo/mpesa-payment/actions/workflows/main.yml/badge.svg)

This is where your description should go. Try and limit it to a paragraph or two, and maybe throw in a mention of what PSRs you support to avoid any confusion with users and contributors.

## Installation

You can install the package via composer:

```bash
composer require evance-odhiambo/mpesa-payment
```

## Usage

after Installation add In array of providers in config/app.php Put

```bash

EvanceOdhiambo\MpesaPayment\MpesaPaymentServiceProvider::class

```
and  In aliases Put

```bash
EvanceMpesa' => EvanceOdhiambo\MpesaPayment\MpesaPaymentFacade::class to aliases
```

Then run 

```bash
php artisan vendor:publish --tag=config 
```

Inside config there is a file called evance-mpesa.php created just confirm

Now copy 

```bash
MPESA_ENV=sandbox
CONSUMER_KEY=
CONSUMER_SECRET=
PAYBILL=174379
SHORTCODE=174379
PASSKEY=
C2B_VALIDATE_CALLBACK=
C2B_CONFIRM_CALLBACK=
CALLBACK_URL=
ACC_REF=

```

to your .env and fill with your details.

1. To Register C2B urls just call the bellow fn fromyour 

```bash

 $response=EvanceMpesa::registerUrls()

```

Successfull registration results to


```bash
{
  "OriginatorCoversationID": "23311-116912840-1",
  "ResponseCode": "0",
  "ResponseDescription": "Success"
}
```




2 To initial Mpesa Express (C2B Payments) call

```bash

 $response=EvanceMpesa::express(1030,'714611350',$acc_ref,'Remarks')

```

Upon successfull stk push


```bash
{
  "MerchantRequestID": "101796-19195815-1",
  "CheckoutRequestID": "ws_CO_29062023231703226715711360",
  "ResponseCode": "0",
  "ResponseDescription": "Success. Request accepted for processing",
  "CustomerMessage": "Success. Request accepted for processing"
}

```

### NOTE

```bash
This Package is still under Development
```




### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email evanceodhiambo07@gmail.com instead of using the issue tracker.

## Credits

-   [Evance Odhiambo](https://github.com/Evanda-crypto)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

