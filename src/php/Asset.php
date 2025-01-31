<?php
/*
* @package asset
* @author Carsten Wallenhauer <admin@datapool.info>
* @copyright 2024 to today Carsten Wallenhauer
* @license https://opensource.org/license/mit MIT
*/

declare(strict_types=1);

namespace SourcePot\Asset;

final class Asset{

    public const DEFAULT_UNIT='EUR';
    
    function __construct(float $value=0,string $unit='EUR',string $dateTime='now')
    {
        
    }

}
?>