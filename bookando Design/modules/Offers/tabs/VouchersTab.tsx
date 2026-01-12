import React from 'react';
import { Gift, Ticket, Edit2, Trash2 } from 'lucide-react';
import { VoucherItem } from '../../../types';

interface VouchersTabProps {
    vouchers: VoucherItem[];
    onEdit: (voucher: VoucherItem) => void;
    onDelete: (id: string) => void;
}

const VouchersTab: React.FC<VouchersTabProps> = ({ vouchers, onEdit, onDelete }) => {
    return (
        <div className="flex-1 overflow-y-auto p-6">
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden animate-fadeIn">
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th className="p-4 text-xs font-semibold text-slate-500 uppercase">Details</th>
                                <th className="p-4 text-xs font-semibold text-slate-500 uppercase">Type</th>
                                <th className="p-4 text-xs font-semibold text-slate-500 uppercase">Value / Discount</th>
                                <th className="p-4 text-xs font-semibold text-slate-500 uppercase">Limits / Usage</th>
                                <th className="p-4 text-xs font-semibold text-slate-500 uppercase">Status</th>
                                <th className="p-4 text-right"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-200">
                            {vouchers.map((voucher) => (
                                <tr key={voucher.id} className="hover:bg-slate-50 group">
                                    <td className="p-4">
                                        <div className="flex items-center gap-3">
                                            <div className={`p-2 rounded border ${voucher.category === 'GiftCard' ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-indigo-50 text-indigo-600 border-indigo-100'}`}>
                                                {voucher.category === 'GiftCard' ? <Gift size={18} /> : <Ticket size={18} />}
                                            </div>
                                            <div>
                                                <div className="font-bold text-slate-800">{voucher.title}</div>
                                                {voucher.category === 'Promotion' && <div className="text-xs text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded w-fit mt-1">{voucher.code}</div>}
                                            </div>
                                        </div>
                                    </td>
                                    <td className="p-4 text-sm text-slate-600">
                                        {voucher.category === 'GiftCard' ? 'Gift Product' : 'Promo Code'}
                                    </td>
                                    <td className="p-4 text-sm font-medium text-slate-700">
                                        {voucher.category === 'GiftCard' ? (
                                            voucher.allowCustomAmount ? (
                                                <span className="text-slate-500 italic">User defined (${voucher.minCustomAmount} - ${voucher.maxCustomAmount})</span>
                                            ) : (
                                                <span>${voucher.fixedValue} (Fixed)</span>
                                            )
                                        ) : (
                                            voucher.discountType === 'Percentage' ? `${voucher.discountValue}% OFF` : `$${voucher.discountValue} OFF`
                                        )}
                                    </td>
                                    <td className="p-4 text-sm text-slate-600">
                                        {voucher.category === 'GiftCard' ? (
                                            <span className="text-xs text-slate-400">Generated on purchase</span>
                                        ) : (
                                            <div className="w-32">
                                                <div className="flex justify-between text-xs mb-1">
                                                    <span>{voucher.uses} used</span>
                                                    <span>{voucher.maxUses ? voucher.maxUses : '∞'}</span>
                                                </div>
                                                <div className="w-full bg-slate-100 rounded-full h-1.5">
                                                    <div className="bg-brand-500 h-1.5 rounded-full" style={{ width: voucher.maxUses ? `${(voucher.uses! / voucher.maxUses) * 100}%` : '5%' }}></div>
                                                </div>
                                            </div>
                                        )}
                                    </td>
                                    <td className="p-4">
                                        <span className={`text-xs font-medium px-2 py-1 rounded-full border ${
                                            voucher.status === 'Active' ? 'bg-emerald-50 border-emerald-100 text-emerald-700' :
                                            voucher.status === 'Depleted' ? 'bg-slate-100 border-slate-200 text-slate-600' : 'bg-rose-50 border-rose-100 text-rose-700'
                                        }`}>
                                            {voucher.status}
                                        </span>
                                    </td>
                                    <td className="p-4 text-right flex justify-end gap-2">
                                        <button onClick={() => onEdit(voucher)} className="p-2 text-slate-400 hover:text-brand-600 hover:bg-slate-100 rounded transition-colors"><Edit2 size={16} /></button>
                                        <button onClick={() => onDelete(voucher.id)} className="p-2 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded transition-colors"><Trash2 size={16} /></button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default VouchersTab;
