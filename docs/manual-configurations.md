# 🛠 Manual configurations

> [!Warning]
> The steps described below can also be done automatically by running the [🚀 Getting Started](../README.md#-getting-started) wizard (`php artisan filament-user-attributes:wizard`) command.
> 
> The information below is only for those who want to manually configure the package.

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

2. Add the `ConfiguresUserAttributesContract` interface and `ConfiguresUserAttributes` trait to the model that should be able to configure user attributes (e.g. a user or tenant model):

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

3. Have the resource use the `UserAttributesResource` trait.

4. In your resource wrap the array for your fields (in the `form` method) in `self::withUserAttributeFields()`.

5. Similarly wrap the array for your columns (in the `table` method) in `self::withUserAttributeColumns()`.

6. Have the resource implement the `UserAttributesConfigContract` method `getUserAttributesConfig()` to return the model instance that decides which custom user attributes are available. This is the model with the `ConfiguresUserAttributes` trait (like the user or tenant).

**Your resource should now look something like this:**

```php
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

class ProductResource extends Resource implements UserAttributesConfigContract
{
    // This will add the user attributes to the form and table, based on the configuration for the Product model, specified by the User.
    use UserAttributesResource;

    protected static ?string $model = Product::class;

    // This is the model that will be asked for the user attributes configuration. For example a user or tenant model.
    public static function getUserAttributesConfig(): ?ConfiguresUserAttributesContract
    {
        /** @var \App\Models\User */
        $user = Auth::user();

        return $user;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(
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

Finally you need to show the user attributes configuration form somewhere. That way users can actually configure their custom attributes for the resource.

7. Create a resource and inherit from the `UserAttributeConfigResource` class:

    ```php
    // app/Filament/Resources/UserAttributeConfigResource.php
    namespace App\Filament\Resources;

    use Luttje\FilamentUserAttributes\Filament\Resources\UserAttributeConfigResource as BaseUserAttributeConfigResource;

    class UserAttributeConfigResource extends BaseUserAttributeConfigResource
    {
        protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    }
    ```

    *Or you can create your own resource from scratch. See the [source code](../src/Filament/Resources/) for inspiration.*

## 🎈 Filament Livewire Components

Filament Livewire components work roughly the same. We also implement the `UserAttributesConfigContract` method `getUserAttributesConfig` so the configuration is retrieved from the model that specifies configurations.

```php
use Luttje\FilamentUserAttributes\Contracts\UserAttributesConfigContract;
use Luttje\FilamentUserAttributes\Contracts\ConfiguresUserAttributesContract;
use Luttje\FilamentUserAttributes\Traits\UserAttributesResource;

class ProductManageComponent extends Component implements HasForms, HasTable, UserAttributesConfigContract
{
    use UserAttributesResource;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getUserAttributesConfig(): ?ConfiguresUserAttributesContract
    {
        /** @var \App\Models\User */
        $user = Auth::user();

        return $user;
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

    public function form(Form $form): Form
    {
        return $form
            ->schema(
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
