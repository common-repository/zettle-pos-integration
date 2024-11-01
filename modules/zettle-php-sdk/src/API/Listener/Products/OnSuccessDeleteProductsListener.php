<?php

declare(strict_types=1);

// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType

namespace Inpsyde\Zettle\PhpSdk\API\Listener\Products;

use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Zettle\PhpSdk\API\Listener\ApiRestListener;
use Inpsyde\Zettle\PhpSdk\Repository\Zettle\Product\ProductRepositoryInterface;
use Inpsyde\Zettle\Sync\Job\UnlinkImages;
use Inpsyde\Zettle\Sync\Job\UnlinkProductJob;
use Psr\Log\LoggerInterface;

/**
 * Class OnSuccessDeleteProductsListener
 *
 * If we have a successfully response from Zettle with the Product API Client
 * and a Delete operation this will be executed
 *
 * We just unlink the Product locally and also unlink the Product Images with the unlink images job
 *
 * @package Inpsyde\Zettle\PhpSdk\API\Listener\Products
 */
class OnSuccessDeleteProductsListener implements ApiRestListener
{

    /**
     * @var ProductRepositoryInterface
     */
    private $repository;

    /**
     * @var callable
     */
    private $createJob;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * DeleteProductListener constructor.
     *
     * @param ProductRepositoryInterface $repository
     * @param JobRepository $jobRepository
     * @param callable $createJob
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $repository,
        JobRepository $jobRepository,
        callable $createJob,
        LoggerInterface $logger
    ) {

        $this->repository = $repository;
        $this->jobRepository = $jobRepository;
        $this->createJob = $createJob;
        $this->logger = $logger;
    }

    /**
     * @param string $operation
     * @param string $payload
     * @param bool $success
     *
     * @return bool
     */
    public function accepts(string $operation, $payload, bool $success): bool
    {
        if ($operation !== ApiRestListener::DELETE || !$success) {
            return false;
        }

        if (!is_string($payload)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $payload contains product uuid::v1
     *
     * @return bool
     */
    public function execute($payload): bool
    {
        $productId = $this->repository->findByUuid($payload);

        if ($productId === null) {
            return false;
        }

        $this->logger->debug("Product with ID:{$productId} will be deleted.");

        $this->jobRepository->add(...[
            ($this->createJob)(
                UnlinkProductJob::TYPE,
                [
                    'localId' => $productId,
                ]
            ),
            ($this->createJob)(
                UnlinkImages::TYPE,
                [
                    'productId' => $productId,
                ]
            ),
        ]);

        return true;
    }
}
