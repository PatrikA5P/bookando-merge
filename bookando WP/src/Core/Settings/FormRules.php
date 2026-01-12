<?php
namespace Bookando\Core\Settings;

use Bookando\Core\Tenant\TenantManager;

final class FormRules
{
    private static function optionName(?int $tenantId): string {
        return $tenantId ? "bookando_form_rules_{$tenantId}" : "bookando_form_rules_global";
    }
    public static function getAll(): array {
        $tenantId = TenantManager::currentTenantId();
        $opt = get_option(self::optionName($tenantId), []);
        return is_array($opt) ? $opt : [];
    }
    public static function putAll(array $rules): void {
        $tenantId = TenantManager::currentTenantId();
        update_option(self::optionName($tenantId), $rules, false);
    }

    /**
     * Struktur:
     * {
     *   fields: {
     *     first_name: { required: true, visible: true, when: { status_not: ["deleted"] } },
     *     ...
     *   },
     *   groups: {
     *     at_least_one_of: [ ["email","phone"] ]
     *   }
     * }
     */
    public static function get(string $module, string $form = 'admin'): array {
        $all = self::getAll();
        $rules = $all[$module][$form] ?? null;
        return is_array($rules) ? $rules : self::defaults($module, $form);
    }

    public static function merge(string $module, string $form, array $patch): array {
        $all = self::getAll();
        $base = $all[$module][$form] ?? self::defaults($module, $form);
        $merged = array_replace_recursive($base, $patch);
        $all[$module][$form] = $merged;
        self::putAll($all);
        return $merged;
    }

    public static function defaults(string $module, string $form = 'admin'): array {
        $D = [
          'employees' => [
            'admin' => [
              'fields' => [
                'first_name' => ['required'=>true,  'visible'=>true, 'when'=>['status_not'=>['deleted']]],
                'last_name'  => ['required'=>true,  'visible'=>true, 'when'=>['status_not'=>['deleted']]],
                'email'      => ['required'=>false, 'visible'=>true, 'when'=>['status_not'=>['deleted']]],
                'gender'     => ['required'=>false, 'visible'=>true],
                'birthdate'  => ['required'=>false, 'visible'=>true],
              ],
              'groups' => [
                // keine Gruppenregel standardmäßig nötig
              ]
            ],
            'booking' => [
              'fields' => [
                'first_name' => ['required'=>true,  'visible'=>true],
                'last_name'  => ['required'=>true,  'visible'=>true],
                'email'      => ['required'=>false, 'visible'=>true],
                'phone'      => ['required'=>false, 'visible'=>true],
              ],
              'groups' => [
                // mind. eine Kontaktmethode (E-Mail ODER Telefon)
                'at_least_one_of' => [ ['email','phone'] ]
              ]
            ],
          ],
          'customers' => [
            'admin' => [
              'fields' => [
                'first_name' => ['required'=>true,  'visible'=>true, 'when'=>['status_not'=>['deleted']]],
                'last_name'  => ['required'=>true,  'visible'=>true, 'when'=>['status_not'=>['deleted']]],
                'email'      => ['required'=>true,  'visible'=>true, 'when'=>['status_not'=>['deleted']]],
                'gender'     => ['required'=>false, 'visible'=>true],
                'birthdate'  => ['required'=>false, 'visible'=>true],
              ],
              'groups' => []
            ],
            'booking' => [
              'fields' => [
                'first_name' => ['required'=>true, 'visible'=>true],
                'last_name'  => ['required'=>true, 'visible'=>true],
                'email'      => ['required'=>false,'visible'=>true],
                'phone'      => ['required'=>false,'visible'=>true],
              ],
              'groups' => [
                'at_least_one_of' => [ ['email','phone'] ]
              ]
            ],
          ],
        ];
        return $D[$module][$form] ?? ['fields'=>[], 'groups'=>[]];
    }
}
