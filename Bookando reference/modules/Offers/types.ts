/**
 * Shared Types for Offers Module
 */

import { ServiceItem } from '../../../types';

export type ModalTab = 'general' | 'pricing' | 'scheduling' | 'rules' | 'process';

export interface OfferModalProps {
  mode: 'create' | 'edit';
  type: 'service' | 'bundle' | 'voucher';
  initialData: any;
  initialTab?: ModalTab;
  availableServices: ServiceItem[];
  onClose: () => void;
  onSave: (data: any) => void;
}
