<?php
/**
 * Этот файл является частью пакета sizya.
 *
 * PHP version 8
 *
 * @category Exceptions
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */

namespace CashCarryShop\Sizya\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Exception;
use Throwable;

/**
 * Ошибка появляющаяся при появлении ответа
 * от сервера с ошибкой.
 *
 * PHP version 8
 *
 * @category Exceptions
 * @package  Sizya
 * @author   TheWhatis <anton-gogo@mail.ru>
 * @license  Unlicense <https://unlicense.org>
 * @link     https://github.com/cashcarryshop/Sizya
 */
class BadResponseException extends Exception
{
    /**
     * Ответ от сервера.
     *
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * Создание исключения
     *
     * @param ResponseInterface $response Ответ от сервера
     * @param int               $code       Код ошибки
     * @param Throwable         $previous   Предыдущая ошибка
     */
    public function __construct(
        ResponseInterface $response,
        int               $code     = 0,
        Throwable         $previous = null,
    ) {
        $message = \sprintf(
            'Bad Response: [%s] code. [%s]',
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * Получить response.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
