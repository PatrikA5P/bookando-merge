/**
 * Customer Service
 *
 * API-Service für Customer Management
 */

import apiClient from './api';

export interface CustomerFilters {
  status?: 'ACTIVE' | 'INACTIVE' | 'ARCHIVED';
  search?: string;
}

export interface Customer {
  id: string;
  organizationId: string;
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  address?: string;
  zip?: string;
  city?: string;
  country?: string;
  birthday?: string;
  gender?: string;
  customFields?: any;
  status: 'ACTIVE' | 'INACTIVE' | 'ARCHIVED';
  createdAt: string;
  updatedAt: string;
  _count?: {
    bookings: number;
    enrollments: number;
  };
}

export interface CreateCustomerData {
  firstName: string;
  lastName: string;
  email: string;
  phone?: string;
  address?: string;
  zip?: string;
  city?: string;
  country?: string;
  birthday?: string;
  gender?: string;
  customFields?: any;
  status?: 'ACTIVE' | 'INACTIVE';
}

class CustomerService {
  /**
   * Liste aller Kunden mit optionalen Filtern
   */
  async getCustomers(filters?: CustomerFilters): Promise<Customer[]> {
    return apiClient.get<Customer[]>('/customers', { params: filters as any });
  }

  /**
   * Einzelner Kunde nach ID
   */
  async getCustomer(id: string): Promise<Customer> {
    return apiClient.get<Customer>(`/customers/${id}`);
  }

  /**
   * Neuen Kunden erstellen
   */
  async createCustomer(data: CreateCustomerData): Promise<Customer> {
    return apiClient.post<Customer>('/customers', data);
  }

  /**
   * Kunden aktualisieren
   */
  async updateCustomer(id: string, data: Partial<CreateCustomerData>): Promise<Customer> {
    return apiClient.put<Customer>(`/customers/${id}`, data);
  }

  /**
   * Kunden löschen (archivieren)
   */
  async deleteCustomer(id: string): Promise<void> {
    return apiClient.delete(`/customers/${id}`);
  }

  /**
   * Kunden nach Email suchen
   */
  async findByEmail(email: string): Promise<Customer | null> {
    const customers = await this.getCustomers({ search: email });
    return customers.find((c) => c.email.toLowerCase() === email.toLowerCase()) || null;
  }
}

export const customerService = new CustomerService();
export default customerService;
