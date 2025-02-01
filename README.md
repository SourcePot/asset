# Asset package

This package provides the asset class that is used to create objects that represent an asset. The main properties of an asset are "value", "unit" and "dateTime", e.g. 1 (value) Euro (unit) at 12/03/2023. These three properties need to be provided to the contructor of a new asset object. When a diffenrent unit is set, the exchangerate at the asset objects dateTime will be applied. The European Central Bank exhchage rates are used. If the provided date is a weekend (such in the code sample below) or bank holiday, the latest valid rate before that date will be used.

```
require_once('../php/Rates.php');
require_once('../php/Asset.php');

// asset object creation
$asset = new \SourcePot\Asset\Asset(1234.56,'JPY',new \DateTime("2015-08-23"));

// setting a new unit
$asset->setUnit("USD");

echo $asset; // will show 10.06 USD (2015-08-21T23:00:00+01:00) 

```

An evaluation web page is provided with this package. The webpage allows flexible creation of an asset object, it's conversion to US-Dollars and yearly interest added.

<img src="./assets/evaluation-page.png" alt="Evaluation web page" style="width:100%"/>