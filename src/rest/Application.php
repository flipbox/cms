<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\rest;

use Craft;
use craft\base\ApplicationTrait;
use craft\base\Plugin;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\web\ServiceUnavailableHttpException;
use yii\base\InvalidRouteException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use craft\web\Request;

/**
 * Craft Web Application class
 *
 * @property Request             $request          The request component
 * @property \craft\web\Response $response         The response component
 * @property Session             $session          The session component
 * @property UrlManager          $urlManager       The URL manager for this application
 * @property User                $user             The user component
 *
 * @method Request                                getRequest()      Returns the request component.
 * @method \craft\web\Response                    getResponse()     Returns the response component.
 * @method Session                                getSession()      Returns the session component.
 * @method UrlManager                             getUrlManager()   Returns the URL manager for this application.
 * @method User                                   getUser()         Returns the user component.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Application extends \yii\web\Application
{

    use ApplicationTrait;

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        Craft::$app = $this;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->_init();
    }

    /**
     * Handles the specified request.
     *
     * @param Request $request the request to be handled
     *
     * @return Response the resulting response
     * @throws HttpException
     * @throws ServiceUnavailableHttpException
     * @throws \craft\errors\DbConnectException
     * @throws ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function handleRequest($request): Response
    {
//        // If this is a resource request, we should respond with the resource ASAP
//        $this->_processResourceRequest();
//
//        $headers = $this->getResponse()->getHeaders();
//
//        if ($request->getIsCpRequest()) {
//            // Prevent robots from indexing/following the page
//            // (see https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag)
//            $headers->set('X-Robots-Tag', 'none');
//
//            // Prevent some possible XSS attack vectors
//            $headers->set('X-Frame-Options', 'SAMEORIGIN');
//            $headers->set('X-Content-Type-Options', 'nosniff');
//        }
//
//        // Send the X-Powered-By header?
//        if ($this->getConfig()->getGeneral()->sendPoweredByHeader) {
//            $headers->set('X-Powered-By', $this->name);
//        } else {
//            // In case PHP is already setting one
//            header_remove('X-Powered-By');
//        }

        // Process install requests
//        if (($response = $this->_processInstallRequest($request)) !== null) {
//            return $response;
//        }

//        // Check if the app path has changed.  If so, run the requirements check again.
//        if (($response = $this->_processRequirementsCheck($request)) !== null) {
//            throw new ServiceUnavailableHttpException();
////            $this->_unregisterDebugModule();
//
//            return $response;
//        }

//        // Makes sure that the uploaded files are compatible with the current database schema
//        if (!$this->getUpdates()->getIsCraftSchemaVersionCompatible()) {
////            $this->_unregisterDebugModule();
//
////            if ($request->getIsCpRequest()) {
////                $version = $this->getInfo()->version;
////                $url = App::craftDownloadUrl($version);
////
////                throw new HttpException(200, Craft::t('app', 'Craft CMS does not support backtracking to this version. Please upload Craft CMS {url} or later.', [
////                    'url' => "[{$version}]({$url})",
////                ]));
////            } else {
//                throw new ServiceUnavailableHttpException();
////            }
//        }

        // getIsCraftDbMigrationNeeded will return true if we're in the middle of a manual or auto-update for Craft itself.
        // If we're in maintenance mode and it's not a site request, show the manual update template.
        if ($this->getUpdates()->getIsCraftDbMigrationNeeded()) {
            throw new ServiceUnavailableHttpException();
//            return $this->_processUpdateLogic($request) ?: $this->getResponse();
        }

        // If there's a new version, but the schema hasn't changed, just update the info table
        if ($this->getUpdates()->getHasCraftVersionChanged()) {
            $this->getUpdates()->updateCraftVersionInfo();

//            // Clear the template caches in case they've been compiled since this release was cut.
//            FileHelper::clearDirectory($this->getPath()->getCompiledTemplatesPath());
        }

//        // If the system is offline, make sure they have permission to be here
//        $this->_enforceSystemStatusPermissions($request);

        // Check if a plugin needs to update the database.
        if ($this->getUpdates()->getIsPluginDbUpdateNeeded()) {
            throw new ServiceUnavailableHttpException();
//            return $this->_processUpdateLogic($request) ?: $this->getResponse();
        }

//        // If this is a non-login, non-validate, non-setPassword CP request, make sure the user has access to the CP
//        if ($request->getIsCpRequest() && !($request->getIsActionRequest() && $this->_isSpecialCaseActionRequest($request))) {
//            $user = $this->getUser();
//
//            // Make sure the user has access to the CP
//            if ($user->getIsGuest()) {
//                return $user->loginRequired();
//            }
//
//            if (!$user->checkPermission('accessCp')) {
//                throw new ForbiddenHttpException();
//            }
//
//            // If they're accessing a plugin's section, make sure that they have permission to do so
//            $firstSeg = $request->getSegment(1);
//
//            if ($firstSeg !== null) {
//                /** @var Plugin|null $plugin */
//                $plugin = $plugin = $this->getPlugins()->getPlugin($firstSeg);
//
//                if ($plugin && !$user->checkPermission('accessPlugin-'.$plugin->id)) {
//                    throw new ForbiddenHttpException();
//                }
//            }
//        }

//        // If this is an action request, call the controller
//        if (($response = $this->_processActionRequest($request)) !== null) {
//            return $response;
//        }

        // If we're still here, finally let Yii do it's thing.
        return parent::handleRequest($request);
    }

}
