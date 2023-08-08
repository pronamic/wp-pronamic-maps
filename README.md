# Pronamic Maps

## Providers

- [PDOK (Netherlands/Dutch)](#pdok-netherlands--dutch)
- [Google Maps](#google-maps)

### PDOK (Netherlands / Dutch)

To autocomplete addresses via PDOK we use the following API:
https://api.pdok.nl/bzk/locatieserver/search/v3_1/free

#### FAQ

_In Dutch:_

> **Kan ik als bedrijf of als particulier gebruik maken van PDOK?**
> 
> Als bedrijf of particulier kan je van de PDOK diensten gebruik maken op basis van het PDOK Fair Use dienstenniveau. Hiervoor is geen aanmelding bij PDOK nodig. Het gebruik gaat volgens de [Producten en Diensten Catalogus voor Afnemers](https://www.pdok.nl/documents/1901824/4016976/PDOK+-+Producten-+en+Diensten+Catalogus+-+Afnemers+van+Data.pdf).

> **Moet ik betalen voor het gebruik van de PDOK services?**
>
> Nee, alle huidige aangeboden services zijn vrij van kosten te gebruiken. 

> **Wat gebeurt er als we vinden dat je in strijd met de Fair use policy handelt?**
>
> We proberen altijd eerst contact met je op te nemen om in onderling overleg het probleem op te lossen. Bijvoorbeeld om na te gaan of je niet op PDOK Basis kunt overstappen. Een ander alternatief is dat je eigen webservices ontwikkelt op basis van de beschikbare PDOK open data bestanden.
> 
> Omdat je je voor PDOK Fair Use niet hoeft aan te melden, kunnen wij niet altijd achterhalen wie in strijd met de Fair Use Policy handelt (te vinden in Producten en Diensten Catalogus voor Afnemers). In dit soort gevallen en bij acute problemen, zullen we zonder waarschuwing de dienstverlening stoppen (het betreffende IP-adres of IP-adresreeks blokkeren). Je kan in dat geval via BeheerPDOK@kadaster.nl contact met ons opnemen om te bespreken hoe we de dienstverlening kunnen continueren.

**Source:** https://www.pdok.nl/faq

### Google Maps

To autocomplete addresses via Google we use the following API:
https://maps.googleapis.com/maps/api/geocode/json

#### Pricing

https://cloud.google.com/maps-platform/pricing/sheet

## HTML `autocomplete` attribute

The Pronamic Maps autocomplete features uses the HTML `autocomplete` attribute:

| `autcomplete="?"` | Description |
| ----------------- | ----------- |
| `postal-code`     | A postal code (in the United States, this is the ZIP code). |
| `address-level2`  | The second administrative level, in addresses with at least two of them. In countries with two administrative levels, this would typically be the city, town, village, or other locality in which the address is located. |
| `address-line1`   | Each individual line of the street address. These should only be present if the "street-address" is not present. |

https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/autocomplete

## npm scripts

- npm run js-build

## Links

- https://github.com/BaguettePHP/http-accept-language
- https://www.php.net/manual/en/locale.acceptfromhttp.php
- https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Language
- https://github.com/auraphp/Aura.Accept
- https://github.com/supportpal/accept-language-parser
- https://github.com/rocketip/PHPLocale

[![Pronamic - Work with us](https://github.com/pronamic/brand-resources/blob/main/banners/pronamic-work-with-us-leaderboard-728x90%404x.png)](https://www.pronamic.eu/contact/)
