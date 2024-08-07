<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Pagination\Cursor;

use Illuminate\Pagination\Cursor as LaravelCursor;
use LaravelJsonApi\Core\Schema\IdParser;

final readonly class CursorParser
{
    /**
     * CursorParser constructor.
     *
     * @param IdParser $idParser
     * @param string $keyName
     */
    public function __construct(private IdParser $idParser, private string $keyName)
    {
    }

    /**
     * @param LaravelCursor $cursor
     * @return string
     */
    public function encode(LaravelCursor $cursor): string
    {
        $key = $cursor->parameter($this->keyName);

        if (!$key) {
            return $cursor->encode();
        }

        $encodedId = $this->idParser->encode($key);
        $parameters = $cursor->toArray();
        unset($parameters['_pointsToNextItems']);
        $parameters[$this->keyName] = $encodedId;

        $newCursor = new LaravelCursor($parameters, $cursor->pointsToNextItems());
        return $newCursor->encode();
    }

    /**
     * @param Cursor $cursor
     * @return LaravelCursor|null
     */
    public function decode(Cursor $cursor): ?LaravelCursor
    {
        $encodedCursor = $cursor->isBefore() ? $cursor->getBefore() : $cursor->getAfter();
        if (!is_string($encodedCursor)) {
            return null;
        }

        $parameters = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $encodedCursor)), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        $pointsToNextItems = $parameters['_pointsToNextItems'];
        unset($parameters['_pointsToNextItems']);
        if (isset($parameters[$this->keyName])) {
            $parameters[$this->keyName] = $this->idParser->decode(
                $parameters[$this->keyName],
            );
        }

        return new LaravelCursor($parameters, $pointsToNextItems);
    }
}
