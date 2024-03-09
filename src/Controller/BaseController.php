<?php

namespace Djc\Symfony\Controller;

use Djc\Symfony\Service\AclService;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BaseController extends AbstractController
{
    /**
     * @var array $_headers Read all headers into the var for later use
     */
    protected $_headers = [];
    /**
     * @var \Djc\Phalcon\Models\BaseModel $_model constructed Model via initialize construction
     */
    protected $_model;
    /**
     * @var array $_postFields Read all postFields into this var for use in several functions
     */
    protected $_postFields = [];

    /**
     * @var AclService $_aclService
     */
    protected $_aclService;

    // Protected parameters, set in several functions
    protected $_filters = [];
    protected $_filter;
    protected $_runAsAdmin = false;
    protected $_store = [];
    protected $_responseArray = ['success' => false, 'data' => [], 'total' => 0, 'errorMsg' => '', 'readTranslations' => false];
    protected $_orderString = '';

    public $dateFormat = 'd-M-Y';
    public $timeFormat = 'H:i';
    public $dateTimeFormat;
    public $userLanguage = 'nl';


    public function __construct(private readonly Request $request, private readonly RequestStack $requestStack)
    {
    }

    public function initialize()
    {
        $this->dateTimeFormat = $this->dateFormat . ' ' . $this->timeFormat;

        try {
            // Read headers and check access
            $this->_headers = $this->request->headers;
            if ($this->checkAccess() && !$this->request->get('checkAccess', null, false)) {
                if ($this->_headers['Authorization'] !== 'Bearer ' . $this->requestStack->getSession()->get("authToken")) {
                    throw new \Exception('No correct token supplied');
                }
            }

/**
            // Check if database is installed
            $connection = $this->getDI()->get('db');
            if (!$connection->tableExists('migrations')) {
                // Install database with common modules
                $installer = new DatabaseInstaller();
                $installer->setModules($this->config->modules);
                if (!$installer->installDatabase()) {
                    throw new \Exception('Database cannot be installed, call support');
                }
            }
**/
            if ($this->_runAsAdmin) {
                $this->loginAdmin();
            }

            // read parameters from request
            if ($this->request->getMethod() === "GET" || $this->request->getMethod() === "DELETE") {
                $this->_postFields = $this->request->request->all();
            }
            if ($this->request->getMethod() === "POST" || $this->request->getMethod() === "PUT") {
                $this->_postFields = $this->request->request->all();
                if (!is_array($this->_postFields)) {
                    $this->_postFields = [];
                }
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        if (array_key_exists('sortOrder', $this->_postFields) && ($this->request->getMethod() === "GET" || $this->request->getMethod() === "DELETE")) {
            $orders = json_decode($this->_postFields['sortOrder'], true);
            $orderString = '';
            foreach ($orders as $order) {
                $orderString .= $order['field'] . ' ' . $order['direction'] . ', ';
            }
            $this->_orderString = substr($orderString, 0, -2);
        } else {
            $this->_orderString = $this->_model->orderField . ' ' . $this->_model->orderDirection;
        }

        if (array_key_exists('listFields', $this->_postFields)) {
            $wantedListFields = json_decode($this->_postFields['listFields']);
            $currentListFields = $this->_model->getListFields();
            foreach ($wantedListFields as $key => $listField) {
                if (array_key_exists($listField, $currentListFields)) {
                    $wantedListFields[$listField] = $currentListFields[$listField];
                    unset($wantedListFields[$key]);
                }
            }
            $this->_model->setListFields($wantedListFields);
        }

        if (array_key_exists('filters', $this->_postFields)) {
            $this->_filters = json_decode($this->_postFields['filters'], true);
        }

        $this->afterInitialize();
    }

    protected function checkAccess(): bool
    {
        return true;
    }

    protected function loginAdmin(): void
    {
    }

    protected function afterInitialize(): void
    {
    }

    protected function makeFilter(): void
    {
        $criteria = new Criteria();
        foreach ($this->_filters as $key => $filter) {
            $expressionBuilder = Criteria::expr();
            $expression = false;
            switch ($filter['operator']) {
                case 'eq':
                    $expression = $expressionBuilder->eq($filter['field'], $filter['value']);
                    break;
                case 'ne':
                    $expression = $expressionBuilder->neq($filter['field'], $filter['value']);
                    break;
                case 'ge':
                    $expression = $expressionBuilder->gte($filter['field'], $filter['value']);
                    break;
                case 'gt':
                    $expression = $expressionBuilder->gt($filter['field'], $filter['value']);
                    break;
                case 'le':
                    $expression = $expressionBuilder->lte($filter['field'], $filter['value']);
                    break;
                case 'lt':
                    $expression = $expressionBuilder->lt($filter['field'], $filter['value']);
                    break;
                case 'IN':
                    $expression = $expressionBuilder->in($filter['field'], $filter['value']);
                    break;
                case 'LIKE':
                    if (substr($filter['value'], 0, 1) === '%') {
                        $expression = $expressionBuilder->endsWith($filter['field'], substr($filter['value'], 1));
                    }
                    if (substr($filter['value'], -1, 1) === '%') {
                        $expression = $expressionBuilder->startsWith($filter['field'], substr($filter['value'], 0, -1));
                    }
                    break;
                default:
                    continue 2;
            }
            if ($expression) {
                if (array_key_exists('whereClause', $filter) && strtoupper($filter['whereClause']) === 'OR') {
                    $criteria->orWhere($expression);
                } else {
                    $criteria->andWhere($expression);
                }
            }
        }

        if (array_key_exists('limit', $this->_postFields)) {
            $limit = json_decode($this->_postFields['limit']);
            $criteria->setMaxResults($limit->records)->setFirstResult($limit->offset);
        }

        if (array_key_exists('distinct', $this->_postFields)) {
            $this->_filter['group'] = $this->_postFields['distinct'];
        }
        $this->_filter = $criteria->orderBy($this->_orderString);

    }
}