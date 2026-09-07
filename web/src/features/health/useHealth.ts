import { useQuery } from '@tanstack/react-query';
import { getHealth } from './healthService';

export function useHealth() {
  return useQuery({
    queryKey: ['health'],
    queryFn: ({ signal }) => getHealth(signal),
  });
}

