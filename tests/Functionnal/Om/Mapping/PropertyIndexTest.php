<?php declare(strict_types=1);

namespace Talleu\RedisOm\Tests\Functionnal\Om\Mapping;

use PHPUnit\Framework\TestCase;
use Talleu\RedisOm\Om\Mapping\Property;

class PropertyIndexTest extends TestCase
{
    public function testPropertyWithBooleanIndexFalse(): void
    {
        $property = new Property(index: false);

        $this->assertFalse($property->index);
    }

    public function testPropertyWithBooleanIndexTrue(): void
    {
        $property = new Property(index: true);

        $this->assertTrue($property->index);
    }

    public function testPropertyWithSingleIndexType(): void
    {
        $property = new Property(index: Property::INDEX_TEXT);

        $this->assertIsArray($property->index);
        $this->assertEquals([Property::INDEX_TEXT], $property->index);
    }

    public function testPropertyWithArrayOfIndexTypes(): void
    {
        $property = new Property(
            index: ['age_numeric' => Property::INDEX_NUMERIC, 'age_text' => Property::INDEX_TEXT]
        );

        $this->assertIsArray($property->index);
        $this->assertArrayHasKey('age_numeric', $property->index);
        $this->assertArrayHasKey('age_text', $property->index);
        $this->assertEquals(Property::INDEX_NUMERIC, $property->index['age_numeric']);
        $this->assertEquals(Property::INDEX_TEXT, $property->index['age_text']);
    }

    public function testPropertyWithMultipleIndexTypes(): void
    {
        $property = new Property(
            index: [
                'title_text' => Property::INDEX_TEXT,
                'title_tag' => Property::INDEX_TAG,
            ]
        );

        $this->assertCount(2, $property->index);
        $this->assertEquals(Property::INDEX_TEXT, $property->index['title_text']);
        $this->assertEquals(Property::INDEX_TAG, $property->index['title_tag']);
    }

    public function testPropertyWithNumericAndTagIndexes(): void
    {
        $property = new Property(
            index: [
                'price_numeric' => Property::INDEX_NUMERIC,
                'price_tag' => Property::INDEX_TAG,
            ]
        );

        $this->assertIsArray($property->index);
        $this->assertEquals(Property::INDEX_NUMERIC, $property->index['price_numeric']);
        $this->assertEquals(Property::INDEX_TAG, $property->index['price_tag']);
    }

    public function testPropertyWithGeoIndex(): void
    {
        $property = new Property(index: Property::INDEX_GEO);

        $this->assertEquals([Property::INDEX_GEO], $property->index);
    }

    public function testPropertyWithVectorIndex(): void
    {
        $property = new Property(index: Property::INDEX_VECTOR);

        $this->assertEquals([Property::INDEX_VECTOR], $property->index);
    }

    public function testPropertyWithGeoshapeIndex(): void
    {
        $property = new Property(index: Property::INDEX_GEOSHAPE);

        $this->assertEquals([Property::INDEX_GEOSHAPE], $property->index);
    }

    public function testPropertyWithAllIndexTypes(): void
    {
        $property = new Property(
            index: [
                'field_text' => Property::INDEX_TEXT,
                'field_tag' => Property::INDEX_TAG,
                'field_numeric' => Property::INDEX_NUMERIC,
                'field_geo' => Property::INDEX_GEO,
                'field_vector' => Property::INDEX_VECTOR,
                'field_geoshape' => Property::INDEX_GEOSHAPE,
            ]
        );

        $this->assertCount(6, $property->index);
        $this->assertEquals(Property::INDEX_TEXT, $property->index['field_text']);
        $this->assertEquals(Property::INDEX_TAG, $property->index['field_tag']);
        $this->assertEquals(Property::INDEX_NUMERIC, $property->index['field_numeric']);
        $this->assertEquals(Property::INDEX_GEO, $property->index['field_geo']);
        $this->assertEquals(Property::INDEX_VECTOR, $property->index['field_vector']);
        $this->assertEquals(Property::INDEX_GEOSHAPE, $property->index['field_geoshape']);
    }

    public function testPropertyWithInvalidIndexTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Index type INVALID is not supported');

        new Property(index: ['field' => 'INVALID']);
    }

    public function testPropertyWithMultipleInvalidIndexTypesThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Property(
            index: [
                'field1' => 'INVALID_TYPE',
                'field2' => Property::INDEX_TEXT,
            ]
        );
    }

    public function testPropertyWithGetterAndSetter(): void
    {
        $property = new Property(
            index: ['age' => Property::INDEX_NUMERIC],
            getter: 'getAge',
            setter: 'setAge'
        );

        $this->assertEquals('getAge', $property->getter);
        $this->assertEquals('setAge', $property->setter);
    }

    public function testPropertyWithNullIndex(): void
    {
        $property = new Property(index: null);

        $this->assertNull($property->index);
    }

    public function testPropertyIndexConstants(): void
    {
        $this->assertEquals('TEXT', Property::INDEX_TEXT);
        $this->assertEquals('TAG', Property::INDEX_TAG);
        $this->assertEquals('NUMERIC', Property::INDEX_NUMERIC);
        $this->assertEquals('GEO', Property::INDEX_GEO);
        $this->assertEquals('VECTOR', Property::INDEX_VECTOR);
        $this->assertEquals('GEOSHAPE', Property::INDEX_GEOSHAPE);
    }

    public function testPropertyIndexTypesConstant(): void
    {
        $expectedTypes = [
            Property::INDEX_TEXT,
            Property::INDEX_TAG,
            Property::INDEX_NUMERIC,
            Property::INDEX_GEO,
            Property::INDEX_VECTOR,
            Property::INDEX_GEOSHAPE,
        ];

        $this->assertEquals($expectedTypes, Property::INDEX_TYPES);
    }

    /**
     * @dataProvider validIndexTypesProvider
     */
    public function testPropertyWithValidIndexType(string $indexType): void
    {
        $property = new Property(index: $indexType);

        $this->assertIsArray($property->index);
        $this->assertContains($indexType, $property->index);
    }

    public static function validIndexTypesProvider(): array
    {
        return [
            'TEXT' => [Property::INDEX_TEXT],
            'TAG' => [Property::INDEX_TAG],
            'NUMERIC' => [Property::INDEX_NUMERIC],
            'GEO' => [Property::INDEX_GEO],
            'VECTOR' => [Property::INDEX_VECTOR],
            'GEOSHAPE' => [Property::INDEX_GEOSHAPE],
        ];
    }

    /**
     * @dataProvider invalidIndexTypesProvider
     */
    public function testPropertyWithInvalidIndexType(string $invalidType): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Property(index: $invalidType);
    }

    public static function invalidIndexTypesProvider(): array
    {
        return [
            'lowercase text' => ['text'],
            'mixed case' => ['Text'],
            'invalid type' => ['STRING'],
            'empty string' => [''],
            'random string' => ['RANDOM_TYPE'],
        ];
    }
}