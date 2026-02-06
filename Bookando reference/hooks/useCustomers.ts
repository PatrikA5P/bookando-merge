/**
 * useCustomers Hook
 *
 * Custom Hook für Customer Management mit API-Integration
 */

import { useState, useEffect, useCallback } from 'react';
import customerService, { Customer, CustomerFilters, CreateCustomerData } from '../services/customer.service';

export function useCustomers(filters?: CustomerFilters) {
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Kunden laden
  const loadCustomers = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await customerService.getCustomers(filters);
      setCustomers(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load customers');
      console.error('Error loading customers:', err);
    } finally {
      setIsLoading(false);
    }
  }, [filters]);

  // Initialer Load
  useEffect(() => {
    loadCustomers();
  }, [loadCustomers]);

  // Kunden erstellen
  const createCustomer = async (data: CreateCustomerData): Promise<Customer> {
    setError(null);
    try {
      const newCustomer = await customerService.createCustomer(data);
      setCustomers((prev) => [...prev, newCustomer]);
      return newCustomer;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to create customer';
      setError(message);
      throw new Error(message);
    }
  };

  // Kunden aktualisieren
  const updateCustomer = async (id: string, data: Partial<CreateCustomerData>): Promise<Customer> => {
    setError(null);
    try {
      const updatedCustomer = await customerService.updateCustomer(id, data);
      setCustomers((prev) => prev.map((c) => (c.id === id ? updatedCustomer : c)));
      return updatedCustomer;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to update customer';
      setError(message);
      throw new Error(message);
    }
  };

  // Kunden löschen
  const deleteCustomer = async (id: string): Promise<void> {
    setError(null);
    try {
      await customerService.deleteCustomer(id);
      setCustomers((prev) => prev.filter((c) => c.id !== id));
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to delete customer';
      setError(message);
      throw new Error(message);
    }
  };

  return {
    customers,
    isLoading,
    error,
    loadCustomers,
    createCustomer,
    updateCustomer,
    deleteCustomer,
  };
}

export default useCustomers;
