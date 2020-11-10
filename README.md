# Print forms for Eloquent

Easy way to generate docx print forms of invoices, contracts and other documents.

## Usage

Installation

```bash
composer require mnvx/eloquent-print-form
```

Declare models for Eloquent.

Create print form (docx file) using next syntax.

- `${field_name}` - standard field
- `${entity.field_name}` - field from linked entity
- `${entity.field_name|date}` - format field as date
- `${entity.field_name|date|placeholder}` - format field as date and replace to "____________________" if field is empty
- `${entity1.entity2.field_name}` - field from linked entity through one table
- `${entities.field_name}` - for table data

As you may see, it is possible to use pipes, for example: `|date`, `|date|placeholder`. Supported pipes are:
- `placeholder` - replaces empty variable to "____________________".
- `date` - format date
- `dateTime` - format date time
- `int` - format int value
- `decimal` - format decimal value

It is possible also to specify custom pipes.

Generate print form for specified entity

```php
$entity = Contract::find($id);
$printFormProcessor = new PrintFormProcessor();
$templateFile = resource_path('path_to_print_forms/your_print_form.docx');
$tempFileName = $printFormProcessor->process($templateFile, $entity);
```

## Examples 

### Basic example

For example, if there are next models in project.

```php
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $number
 * @property string $start_at
 * @property int $customer_id
 * @property Customer $customer
 * @property ContractAppendix[] $appendixes
 */
class Contract extends Model
{

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
 
    public function appendixes()
    {
        return $this->hasMany(ContractAppendix::class);
    }
}

/**
 * @property string $name
 * @property CustomerCategory $category
 */
class Customer extends Model
{
    public function category()
    {
        return $this->belongsTo(CustomerCategory::class);
    }

}

/**
 * @property string $number
 * @property string $date
 * @property float $tax
 */
class ContractAppendix extends Model
{

    public function items()
    {
        return $this->hasMany(ContractAppendixItem::class, 'appendix_id');
    }

    public function getTaxAttribute()
    {
        $tax = 0;
        foreach ($this->items as $item) {
            $tax += $item->total_amount * (1 - 100 / (100+($item->taxStatus->vat_rate ?? 0)));
        }
        return $tax;
    }
}
```

Example of Laravel's controller action for printing contract.

```php
public function downloadPrintForm(FormRequest $request)
{
    $id = $request->get('id');
    $entity = Contract::find($id);
    $printFormProcessor = new PrintFormProcessor();
    $templateFile = resource_path('path_to_print_forms/your_print_form.docx');
    $tempFileName = $printFormProcessor->process($templateFile, $entity);
    $filename = 'contract_' . $id;
    return response()
        ->download($tempFileName, $filename . '.docx')
        ->deleteFileAfterSend();
}
```

Example of docx template

---

Contract number: ${number|placeholder}  
Contract date: ${start_at|date|placeholder}    
Customer: ${customer.name}  
Customer category: ${customer.category.name|placeholder}

Appendixes:

| Row number                | Appendix number      | Appendix tax      |
| ------------------------- | -------------------- | -----------------:|
| ${appendixes.#row_number} | ${appendixes.number} | ${appendixes.tax} |

---

Example of generated document

---

Contract number: F-123  
Contract date: 12.04.2012    
Customer: IBM  
Customer category: ____________________ 

Appendixes:

| Row number                | Appendix number      | Appendix tax      |
| ------------------------- | -------------------- | -----------------:|
| 1                         | A-1                  | 1234              |
| 2                         | A-1.1                | 0                 |
| 3                         | B-1                  | 10                |

---

### How to specify custom pipes

```php
class CustomPipes extends \Mnvx\EloquentPrintForm\Pipes
{
    // Override
    public function placeholder($value)
    {
        return $value ?: "NO DATA";
    }

    // New pipe
    public function custom($value)
    {
        return "CUSTOM";
    }
}

$entity = Contract::find($id);
$pipes = new CustomPipes();
$printFormProcessor = new PrintFormProcessor($pipes);
$templateFile = resource_path('path_to_print_forms/your_print_form.docx');
$tempFileName = $printFormProcessor->process($templateFile, $entity);
```

### How to specify custom processors for some variables

Example of custom processor for variable `${custom}`.

```php
$tempFileName = $printFormProcessor->process($templateFile, $entity, [
    'custom' => function(TemplateProcessor $processor, string $variable, ?string $value) {
        $inline = new TextRun();
        $inline->addText('by a red italic text', array('italic' => true, 'color' => 'red'));
        $processor->setComplexValue($variable, $inline);
    },
]);
```
