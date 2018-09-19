# Duplicates plugin for CakePHP

[![Build Status](https://travis-ci.org/QoboLtd/cakephp-duplicates.svg?branch=master)](https://travis-ci.org/QoboLtd/cakephp-duplicates)
[![Latest Stable Version](https://poser.pugx.org/qobo/cakephp-duplicates/v/stable)](https://packagist.org/packages/qobo/cakephp-duplicates)
[![Total Downloads](https://poser.pugx.org/qobo/cakephp-duplicates/downloads)](https://packagist.org/packages/qobo/cakephp-duplicates)
[![Latest Unstable Version](https://poser.pugx.org/qobo/cakephp-duplicates/v/unstable)](https://packagist.org/packages/qobo/cakephp-duplicates)
[![License](https://poser.pugx.org/qobo/cakephp-duplicates/license)](https://packagist.org/packages/qobo/cakephp-duplicates)
[![codecov](https://codecov.io/gh/QoboLtd/cakephp-duplicates/branch/master/graph/badge.svg)](https://codecov.io/gh/QoboLtd/cakephp-duplicates)
[![BCH compliance](https://bettercodehub.com/edge/badge/QoboLtd/cakephp-duplicates?branch=master)](https://bettercodehub.com/)

## About

CakePHP 3 plugin for handling duplicated system records.

This plugin is developed by [Qobo](https://www.qobo.biz) for [Qobrix](https://qobrix.com).  It can be used as standalone CakePHP plugin, or as part of the [project-template-cakephp](https://github.com/QoboLtd/project-template-cakephp) installation.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require qobo/cakephp-duplicates
```

## Setup
Load plugin
```
bin/cake plugin load --routes --bootstrap Qobo/Duplicates
```

## Configuration
Sample duplicates configuration:
```json
// config/Modules/Articles/duplicates.json
{
    "byTitle": [
        { "field": "title", "filter": "Qobo\\Duplicates\\Filter\\ExactFilter" }
    ],
    "byBody": [
        { "field": "body", "filter": "Qobo\\Duplicates\\Filter\\StartsWithFilter", "length": 8 }
    ]
}
```

## Mapping duplicates
To map all duplicate records you need to run the following shell command:
```
./bin/cake map_duplicates
```