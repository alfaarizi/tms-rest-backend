<?php

namespace app\components\docker;

use Docker\API\Exception\NetworkDeleteNotFoundException;
use Docker\API\Model\Network;
use Docker\API\Model\NetworksCreatePostBody;
use Docker\API\Model\NetworksIdConnectPostBody;
use Docker\API\Model\NetworksIdDisconnectPostBody;
use Docker\Docker;
use Psr\Http\Message\ResponseInterface;
use Yii;

/**
 * Represents a docker network stack instance
 */
class DockerNetwork
{
    /**
     * Creates a network with bridge driver and default configuration
     * @param string $os Docker Host OS
     * @param string $name network name
     * @return DockerNetwork
     */
    public static function createWithDefaultBridgeConfig(string $os, string $name): DockerNetwork
    {
        $dockerNetwork = new DockerNetwork($os, $name);
        $createPostBody = new NetworksCreatePostBody();
        $createPostBody->setDriver('bridge');
        $createPostBody->setName($name);
        $createPostBody->setCheckDuplicate(true);

        $options = [];
        $options['com.docker.network.bridge.enable_icc'] = 'true';
        $options['com.docker.network.bridge.enable_ip_masquerade'] = 'true';
        $options['com.docker.network.bridge.host_binding_ipv4'] = '0.0.0.0';
        $options['com.docker.network.driver.mtu'] = '1500';
        $createPostBody->setOptions(new \ArrayObject($options));

        $dockerNetwork->create($createPostBody);
        return $dockerNetwork;
    }
    /**
     * Purges all unused container for the specified host os.
     * @param string $os
     * @return void
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public static function deleteUnusedNetworks(string $os)
    {
        $docker = Yii::$container->get(Docker::class, ['os' => $os]);
        $docker->networkPrune();
    }

    /**
     * Creates a DockerNetwork for an already existing and running container by name.
     * @param string $os
     * @param string $existingNetworkName
     * @return DockerNetwork|null
     */
    public static function createForExisting(string $os, string $existingNetworkName): ?DockerNetwork
    {
        $dockerNetwork = new DockerNetwork($os, $existingNetworkName);
        if (empty($dockerNetwork->getNetworkInspectResult())) {
            Yii::error("Network with name [$existingNetworkName] for OS [$os] does not exists", __METHOD__);
            return null;
        }
        return $dockerNetwork;
    }

    private string $name;
    private Docker $docker;
    /**
     * @var \Docker\API\Model\NetworksCreatePostResponse201|\Psr\Http\Message\ResponseInterface|null
     */
    private $networkCreateResult;
    /**
     * @var \Docker\API\Model\Network|ResponseInterface|null
     */
    private $networkInspectResult;

    public function __construct(string $os, string $name)
    {
        $this->docker = Yii::$container->get(Docker::class, ['os' => $os]);
        $this->name = $name;
    }

    /**
     * Creates a new network with the given configuration.
     * Name collision check is based on best effort.
     * @param NetworksCreatePostBody $networkConfig
     * @return void
     */
    public function create(NetworksCreatePostBody $networkConfig)
    {

        if (!$this->isNetworkCreated()) {
            $networkConfig->setName($this->name);
            $networkConfig->setCheckDuplicate(true);
            try {
                $this->networkCreateResult = $this->docker->networkCreate($networkConfig);
                $this->networkInspectResult = $this->docker->networkInspect($this->networkCreateResult->getId());
            } catch (\Exception $e) {
                Yii::error('Failed to create docker network: ' . $e->getMessage() . ", " . $e->getTraceAsString());
            } finally {
                if (!empty($this->networkCreateResult) && !empty($this->networkCreateResult->getWarning())) {
                    Yii::info(
                        'Network [' . $this->name . '] is started with warnings: '
                        . implode(", ", $this->networkCreateResult->getWarning()),
                        __METHOD__
                    );
                }
            }
        }
    }

    /**
     * Deletes this network.
     * Does nothing if network deleted already.
     * @return void
     */
    public function deleteNetwork()
    {
        if ($this->isNetworkCreated()) {
            try {
                $this->docker->networkDelete($this->networkInspectResult->getId());
                $this->networkCreateResult = null;
                $this->networkInspectResult = null;
            } catch (NetworkDeleteNotFoundException $e) {
                Yii::debug('Container [' . $this->name . '] deleted already');
                $this->networkCreateResult = null;
                $this->networkInspectResult = null;
            }
        }
    }

    /**
     * Attaches the container to this network.
     * Does nothing if network not exists.
     * @param string $containerNameOrId
     * @return ResponseInterface|null
     */
    public function attachContainer(string $containerNameOrId): ?ResponseInterface
    {
        if ($this->isNetworkCreated()) {
            $connectPostBody = new NetworksIdConnectPostBody();
            $connectPostBody->setContainer($containerNameOrId);

            return $this->docker->networkConnect($this->name, $connectPostBody);
        }
        return null;
    }

    /**
     * Forcefully detaches a container from this network.
     * Does nothing if the network not exists.
     * @param string $containerNameOrId
     * @return ResponseInterface|null
     */
    public function detachContainer(string $containerNameOrId): ?ResponseInterface
    {
        if ($this->isNetworkCreated()) {
            $disconnectPostBody = new NetworksIdDisconnectPostBody();
            $disconnectPostBody->setContainer($containerNameOrId);
            $disconnectPostBody->setForce(true);

            return $this->docker->networkDisconnect($this->name, $disconnectPostBody);
        }
        return null;
    }

    private function isNetworkCreated(): bool
    {
        return ($this->getNetworkInspectResult() instanceof Network)
            || (
                $this->networkInspectResult instanceof ResponseInterface
                && $this->networkInspectResult->getStatusCode() == 201
            );
    }

    /**
     * @return \Docker\API\Model\Network|ResponseInterface|null
     */
    public function getNetworkInspectResult()
    {
        if (empty($this->networkInspectResult) && empty($this->networkCreateResult)) {
            try {
                $inspect = $this->docker->networkInspect($this->name);
                $this->networkInspectResult = $inspect;
            } catch (\Exception $ignored) {
                Yii::debug("Network inspect failed for container [$this->name]", __METHOD__);
                //only to fetch inspect on pre-existing container
            }
        }
        return $this->networkInspectResult;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
