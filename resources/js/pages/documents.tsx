import { DocumentsContent } from '@/components/documents-content';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Branch, type DocumentCategory, type Document } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

interface DocumentsPageProps {
    branches: Branch[];
    selectedBranchId?: number;
    categories?: DocumentCategory[];
    documents?: Record<number, Document[]>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Documentos',
        href: '/documents',
    },
];

export default function Documents({ 
    branches = [], 
    selectedBranchId = null, 
    categories = [], 
    documents = {} 
}: DocumentsPageProps) {
    const [selectedBranch, setSelectedBranch] = useState<Branch | null>(
        selectedBranchId ? branches.find(b => b.id === selectedBranchId) || null : null
    );
    const [loading, setLoading] = useState(false);
    const [loadedCategories, setLoadedCategories] = useState<Set<number>>(new Set());

    const handleCategoryToggle = (categoryId: number) => {
        if (loadedCategories.has(categoryId)) return;
        
        // Load documents for this category if not already loaded
        if (!documents[categoryId] && selectedBranch) {
            router.get('/documents', { 
                branch: selectedBranch.id, 
                category: categoryId 
            }, {
                preserveScroll: true,
                preserveState: true,
                only: ['documents'],
                onFinish: () => {
                    setLoadedCategories(prev => new Set([...prev, categoryId]));
                }
            });
        }
    };

    const handleDocumentAction = (action: 'download' | 'view', document: Document) => {
        if (action === 'download') {
            // Create a download link
            const link = window.document.createElement('a');
            link.href = `/documents/${document.id}/download`;
            link.download = document.file_name || 'document';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else if (action === 'view') {
            // Open in new tab/window
            if (document.file_url) {
                window.open(document.file_url, '_blank');
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Documentos" />
            
            <DocumentsContent
                selectedBranch={selectedBranch}
                categories={categories}
                documents={documents}
                loading={loading}
                onCategoryToggle={handleCategoryToggle}
                onDocumentAction={handleDocumentAction}
            />
        </AppLayout>
    );
}