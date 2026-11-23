# 🛠 Manual configurations

> [!Warning]
> The steps described below can also be done automatically by running the [🚀 Getting Started](../README.md#-getting-started) wizard (`php artisan filament-user-attributes:wizard`) command.
>
> The information below is only for those who want to manually configure their Filament 4 project (or Filament 5, if you still use the Filament 4 folder structure).

## 📦 Setup models to have custom user attributes

1. Add the `HasUserAttributesContract` interface and `HasUserAttributes` trait to one or more models you want to have custom user attributes on.

    ```php
    use Luttje\FilamentUserAttributes\Contracts\HasUserAttributesContract;
    use Luttje\FilamentUserAttributes\Traits\HasUserAttributes;

    class Product extends Model implements HasUserAttributesContract
    {
        use HasUserAttributes;
    }
    ```

## 📎 User configured attributes for models

You can let your users configure which custom attributes should be added to your filament tables and forms.

1. Add the `ConfiguresUserAttributesContract` interface and `ConfiguresUserAttributes` trait to the model that should be able to configure user attributes (e.g. a user or tenant model):

    ```php
    use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
    use Luttje\FilamentUserAttributes\Traits\ConfiguresUserAttributes;

    class User extends Authenticatable implements ConfiguresUserAttributesContract
    {
        // This trait will store the user attributes configurations for each user. Meaning each user can specify different attributes to appear on forms and tables for them.
        // In most cases you'll want this on a Tenant, Team or something similar.
        use ConfiguresUserAttributes;
    }
    ```

Now it's time to setup a resource that should display the user attributes and allow editting them:

1. Add the `UserAttributesResourceContract` interface and `UserAttributesResource` trait to the resource.

2. In your resource wrap the array for your fields (in the `form` method) in `self::withUserAttributeFields()`.

3. Similarly wrap the array for your columns (in the `table` method) in `self::withUserAttributeColumns()`.

**Your resource should now look something like this:**

```php
use Luttje\FilamentUserAttributes\Contracts\UserAttributesResourceContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

class ProductResource extends Resource implements UserAttributesResourceContract
{
    // This will add the user attributes to the form and table, based on the configuration for the Product model, specified by the User.
    use UserAttributesResource;

    protected static ?string $model = Product::class;

    public static function form(Schema $form): Schema
    {
        return $form
            ->components(
                // Wrap your schema with:
                self::withUserAttributeFields([
                    // You add non user attribute fields here as you normally would in the `form` method, e.g:
                    TextInput::make('name'),
                ])
            )
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(
                // Wrap your columns with:
                self::withUserAttributeColumns([
                    // You add non user attribute columns here as you normally would in the `table` method, e.g:
                    Tables\Columns\TextColumn::make('name')
                        ->sortable()
                        ->searchable()
                ])
            )
            ->filters([
                // etc...
            ]);
    }
}
```

### 👤 Choosing who configures the attributes

The model with the `ConfiguresUserAttributes` trait owns the configurations. By default this is the current Filament tenant, or otherwise the logged in user (whichever implements `ConfiguresUserAttributesContract`).

To find the owner differently for all resources, tell us how in your `AppServiceProvider`'s `boot` method:

```php
use Luttje\FilamentUserAttributes\Facades\FilamentUserAttributes;

FilamentUserAttributes::resolveOwnerUsing(function (string $resource) {
    return auth()->user()?->team;
});
```

Or implement `getUserAttributesOwner()` on a single resource:

```php
public static function getUserAttributesOwner(): ?ConfiguresUserAttributesContract
{
    return auth()->user()?->team;
}
```

When no owner is found, no configurations are shown or saved.

Finally you need to show the user attributes configuration form somewhere. That way users can actually configure their custom attributes for the resource.

1. Create a resource and inherit from the `UserAttributeConfigResource` class:

    ```php
    // app/Filament/Resources/UserAttributeConfigResource.php
    namespace App\Filament\Resources;

    use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource as BaseUserAttributeConfigResource;

    class UserAttributeConfigResource extends BaseUserAttributeConfigResource
    {
        protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    }
    ```

    *Or you can create your own resource from scratch. See the [source code](../src/Filament/Resources/) for inspiration.*

## 🎈 Filament Livewire Components

Filament Livewire components work roughly the same, but you additionally need to implement a static `getModel` method that returns the model class that should be used for the component.

```php
use Luttje\FilamentUserAttributes\Contracts\UserAttributesResourceContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

class ProductManageComponent extends Component implements HasForms, HasTable, UserAttributesResourceContract
{
    use UserAttributesResource;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }
    
    public static function getModel(): string
    {
        return Product::class;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query())
            ->columns(
                // Wrap your columns with:
                self::withUserAttributeColumns([
                    TextColumn::make('slug'),
                    TextColumn::make('name'),
                ])
            );
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components(
                // Wrap your schema with:
                self::withUserAttributeFields([
                    TextInput::make('name'),
                ])
            )
            ->statePath('data')
            ->model(Product::class);
    }
}
```

*For a complete example of a Livewire component see [the test mock component here](https://github.com/luttje/filament-user-attributes/blob/main/tests/Fixtures/Livewire/ConfiguredManageComponent.php).*
