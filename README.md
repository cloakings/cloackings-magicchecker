Cloakings MagicChecker
=================

Detect if user is bot or real user using magicchecker.com

## Install

```bash
composer require cloakings/cloakings-magicchecker
```

## Usage

### Basic Usage

Register at https://magicchecker.com/:
- Create campaign
  - set "Safe Page" containing "fake" or "safe" like http://example.com/real.php (IMPORTANT!)
  - set "Promo Page" containing "real" or "money" like http://example.com/fake.php (IMPORTANT!)
- Download file (index.php)
- Get params from the file: CAMPAIGN_ID

```php
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$cloaker = \Cloakings\CloakingsMagicChecker\MagicCheckerCloaker(
    campaignId: $campaignId
);
$cloakerResult = $cloaker->handle($request);
```

Check if result mode is `CloakModeEnum::Fake` or `CloakModeEnum::Real` and do something with it.

## Original Logic

Original library is located at `doc/original`.

License for this repository doesn't cover that code.
