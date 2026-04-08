const produto = {
    id: 2050,
    nome: "Geladeira Inverse Samsung",
    preco: 4500.00,
    categorias: ["Eletrodomésticos", "Cozinha", "Linha Branca"]
};

console.log(produto);
console.log("Nome do produto:", produto.nome);
console.log("Primeira categoria:", produto.categorias[0]);





function calcularDesconto(precoOriginal, isFuncionario) {
    if (isFuncionario) {
        return precoOriginal * 0.70;
    } else {
        return precoOriginal;
    }
}

console.log(calcularDesconto(100, true));  
console.log(calcularDesconto(100, false)); 





function formatarMoedaBRL(valor) {
  let valorFormatado = valor.toFixed(2);
  
  return "R$ " + valorFormatado;
}

console.log(formatarMoedaBRL(15.5));





function fecharCarrinho(valorProduto, quantidade, valorFrete) {
    if (quantidade <= 0) return 0; 

    const subTotal = valorProduto * quantidade;

    if (subTotal > 200) {
        valorFrete = 0; 
    }

    const totalCompra = subTotal + valorFrete;
    return totalCompra; 
}





function validarCampoVazio(texto) {
  if (texto === null || texto === undefined) {
    return false; 
  }

  const textoLimpo = texto.trim();

  if (textoLimpo.length === 0) {
    return false; 
  }

  return true; 
}





function gerarResumo(nomeCliente, totalCompra) {
    const valorFormatado = totalCompra.toLocaleString('pt-BR', { style : 'currency', currency: 'BRL' });
    return `Olá ${nomeCliente}, o valor total da sua compra é ${valorFormatado}. Obrigado por comprar conosco!`;
     }





// 2. Saudação ao Cliente
function saudarCliente(nome) {
return `Olá, ${nome}! Bem-vindo à loja MaxTech.`;
}

console.log(saudarCliente("Davi"));





// 6. Validador de Senha Forte
function validarSenha(senha) {
return senha.length >= 8 && senha !== "12345678" && senha !== "senha";
}

console.log(validarSenha("12345678")); // false
console.log(validarSenha("minhasenha123")); // true





// 8. Validador de CPF (Tamanho)
function validarTamanhoCPF(cpf) {
let cpfLimpo = cpf.trim();
return cpfLimpo.length === 11 && /^\d+$/.test(cpfLimpo);
}

console.log(validarTamanhoCPF("12345678901")); // true
console.log(validarTamanhoCPF("123")); // false




const nomeProduto = "micro ondas";
let precoProduto = 400.00;
const produtoAtivo = true;
