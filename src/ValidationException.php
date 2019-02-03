<?php declare(strict_types=1);

namespace Mrself\Sync;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ValidationException extends SyncException
{
    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors;

    public function __construct(ConstraintViolationListInterface $errors)
    {
        $this->errors = $errors;
        parent::__construct("Target did not pass validation");
    }

    /**
     * @return ConstraintViolationListInterface
     */
    public function getErrors(): ConstraintViolationListInterface
    {
        return $this->errors;
    }
}