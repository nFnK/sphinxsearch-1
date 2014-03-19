<?php
/**
 * Sphinx Search
 *
 * @link        https://github.com/ripaclub/sphinxsearch
 * @copyright   Copyright (c) 2014,
 *              Leonardo Di Donato <leodidonato at gmail dot com>,
 *              Leonardo Grasso <me at leonardograsso dot com>
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace SphinxSearch\Db\Sql;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\StatementContainerInterface;
use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\AbstractSql;
use Zend\Db\Sql\SqlInterface;
use Zend\Db\Sql\PreparableSqlInterface;
use SphinxSearch\Db\Adapter\Platform\SphinxQL;

class Show extends AbstractSql implements SqlInterface, PreparableSqlInterface
{
    const SHOW = 'show';
    const LIKE = 'like';
    const SHOW_META     = 'META';
    const SHOW_WARNINGS = 'WARNINGS';
    const SHOW_STATUS   = 'STATUS';

    /**
     * @var array Specification array
     */
    protected $specifications = array(
        self::SHOW => 'SHOW %1$s',
        self::LIKE => 'LIKE %1$s',
    );

    /**
     * @var string
     */
    protected $show = self::SHOW_META;

    /**
     * @var string
     */
    protected $like;

    /**
     * @param string $show
     * @throws Exception\InvalidArgumentException
     * @return Show
     */
    public function show($show)
    {
        $show = strtoupper($show);
        if (!in_array($show, array(self::SHOW_META, self::SHOW_WARNINGS, self::SHOW_STATUS))) {
            throw new Exception\InvalidArgumentException(
                'Show must be one of META, WARNING, or STATUS'
            );
        }

        $this->show = $show;
        return $this;
    }

    /**
     * @param string $like
     * @return Show
     */
    public function like($like)
    {
        $this->like = $like;
        return $this;
    }

    /**
     * @param string $key
     * @return string NULL
     */
    public function getRawState($key = null)
    {
        $rawState = array(
            self::SHOW => $this->show,
            self::LIKE => $this->like
        );
        return (isset($key) && array_key_exists($key, $rawState)) ? $rawState[$key] : $rawState;
    }

    /**
     * @param  PlatformInterface  $platform
     * @param  DriverInterface    $driver
     * @param  ParameterContainer $parameterContainer
     * @return array|null
     */
    protected function processLike(PlatformInterface $platform, DriverInterface $driver = null, ParameterContainer $parameterContainer = null)
    {
        if (!$this->like) {
            return null;
        }

        $like = (string) $this->like;

        if ($driver && $parameterContainer) {
            $parameterContainer->offsetSet('like', $like, ParameterContainer::TYPE_STRING);

            return array(
                $driver->formatParameterName('like'),
            );
        }

        return array(
            $platform->quoteValue($like)
        );
    }

    /**
     * Prepare statement
     *
     * @param AdapterInterface $adapter
     * @param StatementContainerInterface $statementContainer
     * @return void
     */
    public function prepareStatement(AdapterInterface $adapter, StatementContainerInterface $statementContainer)
    {
        // ensure statement has a ParameterContainer
        $parameterContainer = $statementContainer->getParameterContainer();
        if (!$parameterContainer instanceof ParameterContainer) {
            $parameterContainer = new ParameterContainer();
            $statementContainer->setParameterContainer($parameterContainer);
        }

        $sqls = array();

        $sqls[self::SHOW] = sprintf($this->specifications[static::SHOW], $this->show);

        $likePart = $this->processLike($adapter->getPlatform(), $adapter->getDriver(), $parameterContainer);
        if (is_array($likePart)) {
            $sqls[self::LIKE] = $this->createSqlFromSpecificationAndParameters($this->specifications[static::LIKE], $likePart);
        }

        $sql = implode(' ', $sqls);

        $statementContainer->setSql($sql);
        return;
    }

    /**
     * Get SQL string for statement
     *
     * @param  null|PlatformInterface $adapterPlatform If null, defaults to SphinxQL
     * @return string
     */
    public function getSqlString(PlatformInterface $adapterPlatform = null)
    {
        // get platform, or create default
        $adapterPlatform = ($adapterPlatform) ?: new SphinxQL();

        $sqls = array();

        $sqls[self::SHOW] = sprintf($this->specifications[static::SHOW], $this->show);

        $likePart = $this->processLike($adapterPlatform);
        if (is_array($likePart)) {
            $sqls[self::LIKE] = $this->createSqlFromSpecificationAndParameters($this->specifications[static::LIKE], $likePart);
        }

        $sql = implode(' ', $sqls);
        return $sql;
    }
}