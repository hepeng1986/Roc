<?php

/**
 * Bootstrap is a mechanism used to do some intial config
 * before a Application run.
 * User may define their own Bootstrap class by inheriting
 * Roc_Bootstrap
 * Any method declared in Bootstrap class with leading "_init",
 * will be called by Roc_Application::bootstrap()
 * one by one according to their defined order.
 *
 */
abstract class Roc_Bootstrap
{

    const Roc_DEFAULT_BOOTSTRAP = 'Bootstrap';

    const Roc_BOOTSTRAP_INITFUNC_PREFIX = '_init';
}
