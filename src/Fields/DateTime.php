<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Fields;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use function config;

class DateTime extends Attribute
{

    /**
     * Should dates be converted to the defined time zone?
     *
     * @var bool
     */
    private bool $useTz = true;

    /**
     * @var string|null
     */
    private ?string $tz = null;

    /**
     * Create a datetime field.
     *
     * @param string $fieldName
     * @param string|null $column
     * @return static
     */
    public static function make(string $fieldName, string $column = null): self
    {
        return new static($fieldName, $column);
    }

    /**
     * Use the provided timezone.
     *
     * @param string $tz
     * @return $this
     */
    public function useTimezone(string $tz): self
    {
        $this->tz = $tz;
        $this->useTz = true;

        return $this;
    }

    /**
     * Retain the timezone provided in the JSON value.
     *
     * @return $this
     */
    public function retainTimezone(): self
    {
        $this->useTz = false;

        return $this;
    }

    /**
     * Get the server-side timezone.
     *
     * @return string
     */
    public function timezone(): string
    {
        if ($this->tz) {
            return $this->tz;
        }

        return $this->tz = config('app.timezone');
    }

    /**
     * @inheritDoc
     */
    protected function deserialize($value)
    {
        $value = parent::deserialize($value);

        return $this->parse($value);
    }

    /**
     * Parse a date time value.
     *
     * @param CarbonInterface|string|null $value
     * @return CarbonInterface|null
     */
    protected function parse($value): ?CarbonInterface
    {
        if (is_null($value)) {
            return null;
        }

        $value = is_string($value) ? Date::parse($value) : Date::instance($value);

        if (true === $this->useTz) {
            return $value->setTimezone($this->timezone());
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function assertValue($value): void
    {
        if (!is_null($value) && (!is_string($value) || empty($value))) {
            throw new \UnexpectedValueException(sprintf(
                'Expecting the value of attribute %s to be a string (datetime).',
                $this->name()
            ));
        }
    }
}
