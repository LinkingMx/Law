import { Badge } from '@/components/ui/badge';
import { BranchStatistics } from '@/components/branch-statistics';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import { type Branch, type DocumentCategory, type Document } from '@/types';
import { ChevronDown, ChevronRight, Folder, FolderOpen, FileText, Download, Eye, Calendar, AlertTriangle, Clock, CheckCircle } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';

interface DocumentsContentProps {
    selectedBranch: Branch | null;
    categories: DocumentCategory[];
    documents: Record<number, Document[]>; // categoryId -> documents
    loading?: boolean;
    onCategoryToggle: (categoryId: number) => void;
    onDocumentAction: (action: 'download' | 'view', document: Document) => void;
}

export function DocumentsContent({ 
    selectedBranch, 
    categories, 
    documents, 
    loading = false,
    onCategoryToggle,
    onDocumentAction 
}: DocumentsContentProps) {
    const [expandedCategories, setExpandedCategories] = useState<Set<number>>(new Set());
    
    const toggleCategory = (categoryId: number) => {
        const newExpanded = new Set(expandedCategories);
        if (newExpanded.has(categoryId)) {
            newExpanded.delete(categoryId);
        } else {
            newExpanded.add(categoryId);
            onCategoryToggle(categoryId);
        }
        setExpandedCategories(newExpanded);
    };
    
    const getDocumentStatusBadge = (document: Document) => {
        if (!document.expire_date) {
            return (
                <Badge variant="outline" className="text-xs">
                    <FileText className="h-3 w-3 mr-1" />
                    Sin vencimiento
                </Badge>
            );
        }
        
        if (document.is_expired) {
            return (
                <Badge variant="destructive" className="text-xs">
                    <AlertTriangle className="h-3 w-3 mr-1" />
                    Vencido
                </Badge>
            );
        }
        
        if (document.is_expiring_soon) {
            return (
                <Badge className="text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                    <Clock className="h-3 w-3 mr-1" />
                    Por vencer
                </Badge>
            );
        }
        
        return (
            <Badge variant="secondary" className="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                <CheckCircle className="h-3 w-3 mr-1" />
                Vigente
            </Badge>
        );
    };
    
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };
    
    if (!selectedBranch) {
        return (
            <div className="flex h-full flex-1 items-center justify-center p-4">
                <div className="text-center">
                    <Folder className="mx-auto h-12 w-12 text-muted-foreground opacity-50" />
                    <h3 className="mt-4 text-lg font-semibold">Selecciona una sucursal</h3>
                    <p className="mt-2 text-sm text-muted-foreground px-4">
                        <span className="lg:hidden">Toca el botón de menú arriba para elegir una sucursal</span>
                        <span className="hidden lg:inline">Elige una sucursal del panel izquierdo para ver sus documentos</span>
                    </p>
                </div>
            </div>
        );
    }
    
    if (loading) {
        return (
            <div className="flex h-full flex-1 flex-col gap-4 p-6">
                <div className="space-y-4">
                    {[1, 2, 3].map((i) => (
                        <Card key={i}>
                            <CardHeader>
                                <Skeleton className="h-6 w-1/3" />
                                <Skeleton className="h-4 w-2/3" />
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-2">
                                    <Skeleton className="h-4 w-full" />
                                    <Skeleton className="h-4 w-3/4" />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        );
    }
    
    return (
        <div className="flex h-full flex-1 flex-col">
            <ScrollArea className="flex-1 p-4 lg:p-6">
                <div className="space-y-6">
                    {/* Branch Header */}
                    <div className="space-y-1">
                        <h1 className="text-2xl lg:text-3xl font-bold">{selectedBranch.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {selectedBranch.address}
                            {categories.length > 0 && (
                                <> • {categories.length} {categories.length === 1 ? 'categoría' : 'categorías'}</>
                            )}
                        </p>
                    </div>
                    
                    {/* Branch Statistics */}
                    <BranchStatistics branch={selectedBranch} />
                    
                    {/* Document Categories */}
                    <div className="space-y-4">
                    {categories.length === 0 ? (
                        <div className="text-center py-12">
                            <Folder className="mx-auto h-12 w-12 text-muted-foreground opacity-50" />
                            <h3 className="mt-4 text-lg font-semibold">No hay categorías</h3>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Esta sucursal no tiene categorías de documentos configuradas
                            </p>
                        </div>
                    ) : (
                        categories.map((category) => {
                            const isExpanded = expandedCategories.has(category.id);
                            const categoryDocuments = documents[category.id] || [];
                            
                            return (
                                <Card key={category.id} className="overflow-hidden">
                                    <Collapsible
                                        open={isExpanded}
                                        onOpenChange={() => toggleCategory(category.id)}
                                    >
                                        <CollapsibleTrigger asChild>
                                            <CardHeader className="cursor-pointer hover:bg-muted/50 transition-colors pb-4">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-3 flex-1 min-w-0">
                                                        {isExpanded ? (
                                                            <FolderOpen className="h-5 w-5 text-blue-600 flex-shrink-0" />
                                                        ) : (
                                                            <Folder className="h-5 w-5 text-muted-foreground flex-shrink-0" />
                                                        )}
                                                        <div className="flex-1 min-w-0">
                                                            <CardTitle className="text-base lg:text-lg truncate">{category.name}</CardTitle>
                                                            {category.description && (
                                                                <CardDescription className="mt-1 text-xs lg:text-sm line-clamp-2">
                                                                    {category.description}
                                                                </CardDescription>
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-2 flex-shrink-0">
                                                        <Badge variant="secondary" className="text-xs hidden sm:flex">
                                                            {categoryDocuments.length} doc
                                                        </Badge>
                                                        <Badge variant="secondary" className="text-xs sm:hidden">
                                                            {categoryDocuments.length}
                                                        </Badge>
                                                        {isExpanded ? (
                                                            <ChevronDown className="h-4 w-4 text-muted-foreground" />
                                                        ) : (
                                                            <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                                        )}
                                                    </div>
                                                </div>
                                            </CardHeader>
                                        </CollapsibleTrigger>
                                        
                                        <CollapsibleContent>
                                            <CardContent className="pt-0">
                                                {categoryDocuments.length === 0 ? (
                                                    <div className="text-center py-8 text-muted-foreground">
                                                        <FileText className="mx-auto h-8 w-8 opacity-50 mb-2" />
                                                        <p className="text-sm">No hay documentos en esta categoría</p>
                                                    </div>
                                                ) : (
                                                    <div className="space-y-3">
                                                        {categoryDocuments.map((document) => (
                                                            <div
                                                                key={document.id}
                                                                className="flex flex-col sm:flex-row sm:items-center rounded-lg border p-3 lg:p-4 hover:bg-muted/50 transition-colors"
                                                            >
                                                                <div className="flex-1 min-w-0">
                                                                    <div className="flex items-start gap-3">
                                                                        <FileText className="h-4 w-4 mt-1 text-muted-foreground flex-shrink-0" />
                                                                        <div className="flex-1 min-w-0">
                                                                            <h4 className="font-medium text-sm leading-5">
                                                                                {document.name}
                                                                            </h4>
                                                                            {document.description && (
                                                                                <p className="text-xs text-muted-foreground mt-1 line-clamp-2">
                                                                                    {document.description}
                                                                                </p>
                                                                            )}
                                                                            <div className="flex flex-wrap items-center gap-1.5 mt-2">
                                                                                {getDocumentStatusBadge(document)}
                                                                                {document.expire_date && (
                                                                                    <Badge variant="outline" className="text-xs hidden sm:flex">
                                                                                        <Calendar className="h-3 w-3 mr-1" />
                                                                                        {formatDate(document.expire_date)}
                                                                                    </Badge>
                                                                                )}
                                                                                {document.has_file && document.formatted_file_size && (
                                                                                    <Badge variant="outline" className="text-xs">
                                                                                        {document.formatted_file_size}
                                                                                    </Badge>
                                                                                )}
                                                                            </div>
                                                                            {/* Mobile date display */}
                                                                            {document.expire_date && (
                                                                                <div className="mt-1 sm:hidden">
                                                                                    <Badge variant="outline" className="text-xs">
                                                                                        <Calendar className="h-3 w-3 mr-1" />
                                                                                        {formatDate(document.expire_date)}
                                                                                    </Badge>
                                                                                </div>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                {document.has_file && (
                                                                    <div className="flex items-center gap-1 mt-3 sm:mt-0 sm:ml-4 justify-end sm:justify-start">
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => onDocumentAction('view', document)}
                                                                            className="h-8 px-2 text-xs"
                                                                        >
                                                                            <Eye className="h-3 w-3 sm:mr-1" />
                                                                            <span className="hidden sm:inline">Ver</span>
                                                                        </Button>
                                                                        <Button
                                                                            variant="ghost"
                                                                            size="sm"
                                                                            onClick={() => onDocumentAction('download', document)}
                                                                            className="h-8 px-2 text-xs"
                                                                        >
                                                                            <Download className="h-3 w-3 sm:mr-1" />
                                                                            <span className="hidden sm:inline">Descargar</span>
                                                                        </Button>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </CardContent>
                                        </CollapsibleContent>
                                    </Collapsible>
                                </Card>
                            );
                        })
                    )}
                    </div>
                </div>
            </ScrollArea>
        </div>
    );
}