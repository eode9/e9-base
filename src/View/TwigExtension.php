<?php
/**
 * Slim Framework (http://slimframework.com)
 *
 * @link      https://github.com/slimphp/Twig-View
 * @copyright Copyright (c) 2011-2015 Josh Lockhart
 * @license   https://github.com/slimphp/Twig-View/blob/master/LICENSE.md (MIT License)
 */
namespace E9\Core\View;

class TwigExtension extends \Twig_Extension
{
    /**
     * @var \SimpleAcl\Acl
     */
    private $acl;

    /**
     * @var \Slim\Interfaces\RouterInterface
     */
    private $router;

    /**
     * @var \Slim\Http\Uri
     */
    private $uri;

    public function __construct($acl, $router, $uri)
    {
        $this->router = $router;
        $this->uri = $uri;
        $this->acl = $acl;
    }

    public function getName()
    {
        return 'app';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('is_allowed', array($this, 'isAllowed')),
            new \Twig_SimpleFunction('gettext', array($this, 'gettext')),
            new \Twig_SimpleFunction('md5', array($this, 'md5')),
        ];
    }

    /**
     * @param \E9\Core\Document\User $user
     * @param string $resource
     * @param string $privilege
     * @return bool
     */
    public function isAllowed($user, $resource, $privilege)
    {
        if ($user->isSuperAdmin())
            return true;

        return $this->acl->isAllowed($user->getUuid(), $resource, $privilege);
    }


    public function gettext($text)
    {
        return gettext($text);
    }

    public function md5($text)
    {
        return md5($text);
    }
}