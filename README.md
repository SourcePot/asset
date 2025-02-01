# Asset package

This package provides the asset class that is used to create objects that represent an asset. The main properties of an asset are "value", "unit" and "dateTime", e.g. 1 (value) Euro (unit) at 12/03/2023. These three properties need to be provided to the contructor of a new asset object. When a diffenrent unit is set, the exchangerate at the asset objects dateTime will be applied. The European Central Bank exhchage rates are used.

```
require_once('../php/Rates.php');
require_once('../php/Asset.php');

$asset = new \SourcePot\Asset\Asset(1234.56,'JPY',new \DateTime("2015-08-23"));
```

An evaluation web page is provided with this package. The webpage allows flexible creation of an asset object, it's conversion to US-Dollars and yearly interest added.
<img src="./assets/evaluation-page.png.png" alt="Evaluation web page" style="width:100%"/>