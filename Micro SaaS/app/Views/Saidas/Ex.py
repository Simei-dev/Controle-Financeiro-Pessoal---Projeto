public class QuickSort {

    public static void quickSort(int[] vetor, int inicio, int fim) {

        if (inicio < fim) {

            int posicaoPivo = particao(vetor, inicio, fim);

            quickSort(vetor, inicio, posicaoPivo - 1);
            quickSort(vetor, posicaoPivo + 1, fim);
        }
    }

    public static int particao(int[] vetor, int inicio, int fim) {

        int pivo = vetor[fim];
        int i = inicio - 1;

        for (int j = inicio; j < fim; j++) {

            if (vetor[j] < pivo) {

                i++;

                int temp = vetor[i];
                vetor[i] = vetor[j];
                vetor[j] = temp;
            }
        }

        int temp = vetor[i + 1];
        vetor[i + 1] = vetor[fim];
        vetor[fim] = temp;

        return i + 1;
    }

    public static void main(String[] args) {

        int[] vetor = {9, 4, 6, 2, 7, 1, 8};

        quickSort(vetor, 0, vetor.length - 1);

        System.out.println("Vetor ordenado:");

        for (int num : vetor) {
            System.out.print(num + " ");
        }
    }
}