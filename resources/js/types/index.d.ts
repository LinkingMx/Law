import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    branches?: Branch[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface Branch {
    id: number;
    name: string;
    address: string;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    documents_count?: number;
    expiring_soon_count?: number;
    expired_count?: number;
    created_at: string;
    updated_at: string;
}

export interface DocumentCategory {
    id: number;
    name: string;
    description: string | null;
    documents_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Document {
    id: number;
    document_category_id: number;
    branch_id: number;
    name: string;
    description: string | null;
    expire_date: string | null;
    file_path: string | null;
    file_name: string | null;
    file_extension: string | null;
    file_size: number | null;
    mime_type: string | null;
    file_metadata: Record<string, any> | null;
    uploaded_by: number | null;
    uploaded_at: string | null;
    created_at: string;
    updated_at: string;
    // Relationships
    document_category?: DocumentCategory;
    branch?: Branch;
    uploader?: User;
    // Computed attributes
    is_expired?: boolean;
    is_expiring_soon?: boolean;
    has_file?: boolean;
    file_url?: string | null;
    formatted_file_size?: string | null;
    file_icon?: string;
    file_type_color?: string;
}
