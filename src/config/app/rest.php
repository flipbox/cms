<?php

return [
    'components' => [
        'user' => function() {
            $stateKeyPrefix = md5('Craft.'.craft\web\User::class.'.'.Craft::$app->id);
            return Craft::createObject([
                'class' => craft\web\User::class,
                'identityClass' => craft\elements\User::class,
                'enableAutoLogin' => false,
                'autoRenewCookie' => false,
                'enableSession' => false,
                'usernameCookie' => null,
                'identityCookie' => null,
                'loginUrl' => null,
                'idParam' => $stateKeyPrefix.'__id',
                'authTimeoutParam' => $stateKeyPrefix.'__expire',
                'absoluteAuthTimeoutParam' => $stateKeyPrefix.'__absoluteExpire',
                'returnUrlParam' => $stateKeyPrefix.'__returnUrl',
            ]);
        },
        'urlManager' => [
            'class' => craft\rest\UrlManager::class,
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false
        ],
        'request' => [
            'class' => craft\rest\Request::class,
            'enableCookieValidation' => false,
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => yii\web\JsonParser::class
            ],
            'acceptableContentTypes' => [
                'application/json' => ['q' => 1, 'version' => '1.0'],
                'application/xml' => ['q' => 1, 'version' => '2.0']
            ]
        ],
        'response' => [
            'class' => craft\rest\Response::class,
            'format' => craft\rest\Response::FORMAT_JSON
        ],
        'errorHandler' => [
            'class' => yii\web\ErrorHandler::class,
            'errorAction' => function() {
                throw new yii\web\NotFoundHttpException(
                    Craft::t('app', 'Resource Not Found.')
                );
            }
        ]
    ],
    'modules' => [
        'debug' => null
    ],
];

