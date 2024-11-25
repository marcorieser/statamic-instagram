<?php

use Illuminate\Support\Facades\Route as Route;
use MarcoRieser\StatamicInstagram\ImageProxy;

Route::get('proxy/{hash}.{extension}', ImageProxy::class)->name('statamic-instagram.proxy');
