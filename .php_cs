<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude('vendor')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->finder($finder)
;