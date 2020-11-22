<?php

declare(strict_types=1);

namespace LaravelJsonApi\Eloquent\Tests\Integration\Fields;

use App\Models\Post;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Tests\Integration\TestCase;

class NumberTest extends TestCase
{

    public function test(): void
    {
        $request = $this->createMock(Request::class);
        $attr = Number::make('failureCount');

        $this->assertSame('failureCount', $attr->name());
        $this->assertSame('failure_count', $attr->column());
        $this->assertTrue($attr->isSparseField());
        $this->assertSame(['failure_count'], $attr->columnsForField());
        $this->assertFalse($attr->isSortable());
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testColumn(): void
    {
        $attr = Number::make('failures', 'failure_count');

        $this->assertSame('failures', $attr->name());
        $this->assertSame('failure_count', $attr->column());
        $this->assertSame(['failure_count'], $attr->columnsForField());
    }

    public function testNotSparseField(): void
    {
        $attr = Number::make('failures')->notSparseField();

        $this->assertFalse($attr->isSparseField());
    }

    public function testSortable(): void
    {
        $query = Post::query();

        $attr = Number::make('failures')->sortable();

        $this->assertTrue($attr->isSortable());
        $attr->sort($query, 'desc');

        $this->assertSame(
            [['column' => 'posts.failures', 'direction' => 'desc']],
            $query->toBase()->orders
        );
    }

    /**
     * @return array
     */
    public function validProvider(): array
    {
        return [
            [1],
            [0.1],
            [null],
        ];
    }

    /**
     * @param $value
     * @dataProvider validProvider
     */
    public function testFill($value): void
    {
        $model = new Post();
        $attr = Number::make('title');

        $attr->fill($model, $value);
        $this->assertSame($value, $model->title);
    }

    /**
     * @return array
     */
    public function invalidProvider(): array
    {
        return [
            [true],
            ['foo'],
            ['0'],
            [''],
            [[]],
            [new \DateTime()],
        ];
    }

    /**
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testFillWithInvalid($value): void
    {
        $model = new Post();
        $attr = Number::make('title');

        $this->expectException(\UnexpectedValueException::class);
        $attr->fill($model, $value);
    }

    public function testFillRespectsMassAssignment(): void
    {
        $model = new Post();
        $attr = Number::make('views');

        $attr->fill($model, 200);
        $this->assertArrayNotHasKey('views', $model->getAttributes());
    }

    public function testUnguarded(): void
    {
        $model = new Post();
        $attr = Number::make('views')->unguarded();

        $attr->fill($model, 200);
        $this->assertSame(200, $model->views);
    }

    public function testDeserializeUsing(): void
    {
        $model = new Post();
        $attr = Number::make('title')->deserializeUsing(
            fn($value) => $value + 200
        );

        $attr->fill($model, 100);
        $this->assertSame(300, $model->title);
    }

    public function testFillUsing(): void
    {
        $post = new Post();
        $attr = Number::make('views')->fillUsing(function ($model, $column, $value) use ($post) {
            $this->assertSame($post, $model);
            $this->assertSame('views', $column);
            $this->assertSame(200, $value);
            $model->views = 300;
        });

        $attr->fill($post, 200);
        $this->assertSame(300, $post->views);
    }

    public function testReadOnly(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('wantsJson')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Number::make('views')->readOnly(
            fn($request) => $request->wantsJson()
        );

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testReadOnlyOnCreate(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('isMethod')
            ->with('POST')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Number::make('views')->readOnlyOnCreate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

    public function testReadOnlyOnUpdate(): void
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(2))
            ->method('isMethod')
            ->with('PATCH')
            ->willReturnOnConsecutiveCalls(true, false);

        $attr = Number::make('views')->readOnlyOnUpdate();

        $this->assertTrue($attr->isReadOnly($request));
        $this->assertFalse($attr->isReadOnly($request));
    }

}
