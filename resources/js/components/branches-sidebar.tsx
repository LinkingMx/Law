import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { type Branch } from '@/types';
import { Building2, FileText, Clock, AlertTriangle } from 'lucide-react';
import { cn } from '@/lib/utils';

interface BranchesSidebarProps {
    branches: Branch[];
    selectedBranchId: number | null;
    onBranchSelect: (branch: Branch) => void;
}

export function BranchesSidebar({ branches, selectedBranchId, onBranchSelect }: BranchesSidebarProps) {
    return (
        <div className="flex h-full w-80 flex-col border-r bg-background">
            <div className="flex h-14 items-center border-b px-4">
                <h2 className="flex items-center gap-2 text-lg font-semibold">
                    <Building2 className="h-5 w-5" />
                    Sucursales
                </h2>
            </div>
            
            <ScrollArea className="flex-1 px-3 py-4">
                <div className="space-y-2">
                    {branches.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-8 text-center text-muted-foreground">
                            <Building2 className="h-12 w-12 mb-4 opacity-50" />
                            <p className="text-sm">No tienes sucursales asignadas</p>
                        </div>
                    ) : (
                        branches.map((branch) => (
                            <Button
                                key={branch.id}
                                variant={selectedBranchId === branch.id ? "secondary" : "ghost"}
                                className={cn(
                                    "h-auto w-full justify-start p-3 text-left",
                                    selectedBranchId === branch.id && "bg-secondary"
                                )}
                                onClick={() => onBranchSelect(branch)}
                            >
                                <div className="flex w-full items-start justify-between">
                                    <div className="flex flex-1 flex-col items-start gap-1">
                                        <div className="flex items-center gap-2">
                                            <Building2 className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-medium text-sm">{branch.name}</span>
                                        </div>
                                        <p className="text-xs text-muted-foreground line-clamp-1">
                                            {branch.address}
                                        </p>
                                        
                                        {/* Document counters */}
                                        <div className="flex items-center gap-1 mt-1">
                                            {typeof branch.documents_count === 'number' && branch.documents_count > 0 && (
                                                <Badge variant="outline" className="text-xs h-5 px-1.5">
                                                    <FileText className="h-3 w-3 mr-1" />
                                                    {branch.documents_count}
                                                </Badge>
                                            )}
                                            
                                            {typeof branch.expiring_soon_count === 'number' && branch.expiring_soon_count > 0 && (
                                                <Badge variant="secondary" className="text-xs h-5 px-1.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                                    <Clock className="h-3 w-3 mr-1" />
                                                    {branch.expiring_soon_count}
                                                </Badge>
                                            )}
                                            
                                            {typeof branch.expired_count === 'number' && branch.expired_count > 0 && (
                                                <Badge variant="destructive" className="text-xs h-5 px-1.5">
                                                    <AlertTriangle className="h-3 w-3 mr-1" />
                                                    {branch.expired_count}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </Button>
                        ))
                    )}
                </div>
                
                {branches.length > 0 && (
                    <>
                        <Separator className="my-4" />
                        <div className="px-3 text-xs text-muted-foreground">
                            <div className="flex items-center gap-2 mb-1">
                                <FileText className="h-3 w-3" />
                                <span>Total documentos</span>
                            </div>
                            <div className="flex items-center gap-2 mb-1">
                                <Clock className="h-3 w-3 text-yellow-600" />
                                <span>Por vencer (30 días)</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <AlertTriangle className="h-3 w-3 text-red-600" />
                                <span>Vencidos</span>
                            </div>
                        </div>
                    </>
                )}
            </ScrollArea>
        </div>
    );
}