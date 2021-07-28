<?php

namespace ABTestingForWP;

class CookieManager {

    public static function useServerSideCookies() {
        $optionsManager = new OptionsManager();
        $renderMethod = $optionsManager->getOption('renderMethod', 'server');
        return 'server' === $renderMethod;
    }

    public static function nameById($testId) {
        return "ab-testing-for-wp_${testId}";
    }

    public static function readLegacyCookie() {
        if (self::useServerSideCookies()) {
            return json_decode(stripslashes($_COOKIE['ab-testing-for-wp']), true);
        }

        return false;
    }

    public static function getAllParticipating() {
        if (self::useServerSideCookies()) {
            $data = [];

            // check if legacy cookie is set
            if (isset($_COOKIE['ab-testing-for-wp'])) {
                $cookieData = self::readLegacyCookie();

                foreach ($cookieData as $testId => $variant) {
                    if ($testId !== 'tracked') {
                        $data[$testId] = $variant;
                    }
                }
            }

            // list all picked variants
            foreach ($_COOKIE as $key => $value) {
                if ($key !== 'ab-testing-for-wp' && strpos($key, 'ab-testing-for-wp_') === 0) {
                    list($variant, $tracked) = explode(":", $value);
                    $testId = substr($key, strlen('ab-testing-for-wp_'));

                    $data[$testId] = $variant;
                }
            }

            return $data;
        }

        return false;
    }

    public static function getCookie($testId) {
        if (self::useServerSideCookies()) {
            return $_COOKIE[self::nameById($testId)];
        }

        return false;
    }

    public static function isAvailable($testId) {
        if (self::useServerSideCookies()) {
            // check if legacy cookie is set
            if (isset($_COOKIE['ab-testing-for-wp'])) {
                $data = self::readLegacyCookie();

                // check if test is set
                if (isset($data[$testId])) {
                    return true;
                }
            }

            // if cookie is set
            if (isset($_COOKIE[self::nameById($testId)])) {
                return true;
            }
        }

        return false;
    }

    public static function getData($testId) {
        if (self::useServerSideCookies()) {
            // find test in legacy cookie format
            if (isset($_COOKIE['ab-testing-for-wp'])) {
                $data = self::readLegacyCookie();

                if (isset($data[$testId])) {
                    return [
                        'variant' => $data[$testId],
                        'tracked' => in_array($data[$testId], $data['tracked']) ? 'P' : 'C'
                    ];
                }
            }

            // find test in single cookie format
            if (self::isAvailable($testId)) {
                list($variant, $tracked) = explode(":", self::getCookie($testId));

                return [
                    'variant' => $variant,
                    'tracked' => $tracked
                ];
            }

            throw new Error("No data in cookies for '$testId'");
        }

        return false;
    }

    public static function setData($testId, $variant, $tracked) {
        if (self::useServerSideCookies()) {
            setcookie(
                self::nameById($testId),
                "$variant:$tracked",
                time() + (60*60*24*30),
                '/'
            );
        }
    }

    public static function removeData($testId) {
        if (self::useServerSideCookies()) {
            $name = self::nameById($testId);
            unset($_COOKIE[$name]);
            setcookie($name, null, -1, '/');
        }
    }
}
