<?php

require_once '../conexao.php';

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $nomeSite;?></title>
  <link rel="stylesheet" href="/assets/style/globalStyles.css?id=<?= time(); ?>"/>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
  <script src="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/dist/notiflix-aio-3.2.8.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/notiflix@3.2.8/src/notiflix.min.css" rel="stylesheet">
</head>
<body>

<?php include('../inc/header.php'); ?>

<section class="w-full py-6 relative">
  <div class="max-w-[850px] mx-auto px-4">
    <div class="relative bg-[var(--bg-color)] text-white p-8 rounded-lg shadow-lg mt-8 shadow-rox">

      <div class="absolute -top-8 left-1/2 transform -translate-x-1/2">
        <img src="/assets/img/credit-icon.svg" alt="Ícone de privacidade" class="w-16 h-16">
      </div>

      <h1 class="text-3xl text-center font-semibold mb-6">Política de Privacidade</h1>
      <h2 class="text-xl text-center font-medium mb-8">Política de Privacidade da <?php echo $nomeSite; ?></h2>

      <div class="prose prose-invert max-w-none">
        <p class="mb-6">A <?php echo $nomeSite; ?> valoriza a privacidade dos usuários e está comprometida com a proteção dos seus dados pessoais. Esta Política de Privacidade explica em detalhes como coletamos, armazenamos, utilizamos, compartilhamos e protegemos suas informações pessoais em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018).</p>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">1. Informações Coletadas</h3>
          <p class="mb-4">A <?php echo $nomeSite; ?> poderá coletar as seguintes categorias de dados pessoais:</p>
          <ul class="list-disc pl-6 space-y-2">
            <li><strong>Dados cadastrais:</strong> nome completo, CPF, endereço, e-mail, número de telefone, data de nascimento.</li>
            <li><strong>Dados financeiros:</strong> informações relacionadas a pagamentos, incluindo detalhes bancários ou informações sobre cartões de crédito/débito (processados por empresas parceiras).</li>
            <li><strong>Dados técnicos e de navegação:</strong> endereço IP, geolocalização, identificadores de dispositivos móveis, tipo de navegador, histórico de acesso e atividade dentro da plataforma.</li>
            <li><strong>Dados de comunicação:</strong> registros de interações via suporte ao cliente ou atendimento.</li>
          </ul>
        </div>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">2. Finalidades do Tratamento</h3>
          <p class="mb-4">Seus dados pessoais são utilizados pela <?php echo $nomeSite; ?> para as seguintes finalidades específicas:</p>
          <ul class="list-disc pl-6 space-y-2">
            <li>Disponibilizar e personalizar os serviços oferecidos pela plataforma;</li>
            <li>Gerenciar pagamentos e cumprir obrigações fiscais e regulatórias;</li>
            <li>Prevenir fraudes e garantir a segurança das transações;</li>
            <li>Realizar atividades de comunicação, marketing, promoções e anúncios personalizados;</li>
            <li>Aprimorar continuamente a qualidade e funcionalidade dos serviços;</li>
            <li>Cumprir obrigações legais, judiciais ou regulatórias aplicáveis;</li>
            <li>Proteger direitos e interesses legítimos da <?php echo $nomeSite; ?> e de terceiros.</li>
          </ul>
        </div>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">3. Compartilhamento de Dados com Terceiros</h3>
          <p class="mb-4">A <?php echo $nomeSite; ?> poderá compartilhar seus dados pessoais com terceiros nas seguintes hipóteses:</p>
          <ul class="list-disc pl-6 space-y-2">
            <li><strong>Prestadores de serviços:</strong> parceiros que auxiliam nas operações da plataforma (processadores de pagamento, serviços de hospedagem, suporte ao cliente, marketing digital e análise de dados);</li>
            <li><strong>Obrigação legal:</strong> mediante ordem judicial, requerimento de autoridades públicas ou obrigação regulatória;</li>
            <li><strong>Transações empresariais:</strong> fusão, aquisição ou transferência de controle societário ou ativos;</li>
            <li><strong>Consentimento expresso do usuário:</strong> quando autorizado expressamente pelo usuário.</li>
          </ul>
        </div>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">4. Segurança da Informação</h3>
          <p>Adotamos rigorosas medidas técnicas, administrativas e organizacionais para garantir a segurança e confidencialidade dos seus dados pessoais contra acessos não autorizados, alteração, destruição ou divulgação indevida.</p>
        </div>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">5. Uso de Cookies</h3>
          <p class="mb-4">A plataforma utiliza cookies e tecnologias similares para:</p>
          <ul class="list-disc pl-6 space-y-2">
            <li>Autenticação e segurança;</li>
            <li>Analisar e monitorar o desempenho;</li>
            <li>Melhorar sua experiência através de personalização;</li>
            <li>Entregar anúncios relevantes e segmentados;</li>
            <li>Realizar pesquisas e relatórios estatísticos sobre uso.</li>
          </ul>
          <p class="mt-4">Você pode gerenciar suas preferências sobre cookies nas configurações do navegador, contudo, desativá-los poderá prejudicar funcionalidades da plataforma.</p>
        </div>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">6. Retenção e Armazenamento de Dados</h3>
          <p>Seus dados serão armazenados somente pelo período necessário para atingir as finalidades descritas ou enquanto necessário para cumprir obrigações legais ou regulatórias. Após o período determinado, os dados serão excluídos ou anonimizados permanentemente.</p>
        </div>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">7. Direitos dos Usuários (LGPD)</h3>
          <p class="mb-4">De acordo com a LGPD, você possui direitos que incluem, entre outros:</p>
          <ul class="list-disc pl-6 space-y-2">
            <li>Confirmação e acesso aos seus dados;</li>
            <li>Correção de dados incompletos, inexatos ou desatualizados;</li>
            <li>Anonimização, bloqueio ou eliminação de dados desnecessários ou tratados em desacordo com a lei;</li>
            <li>Portabilidade de dados para outro fornecedor mediante requisição;</li>
            <li>Eliminação dos dados pessoais tratados com consentimento, exceto quando houver obrigação legal;</li>
            <li>Informações sobre compartilhamento dos seus dados;</li>
            <li>Revogação do consentimento a qualquer momento.</li>
          </ul>
          <p class="mt-4">Para exercer esses direitos, entre em contato pelo e-mail indicado na seção "Contato".</p>
        </div>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">8. Transferências Internacionais</h3>
          <p>Seus dados pessoais poderão ser transferidos para fornecedores de serviços localizados fora do Brasil. Nesses casos, garantiremos o cumprimento das exigências legais e a proteção adequada dos seus dados pessoais conforme previsto pela LGPD.</p>
        </div>

        <div class="mb-8">
          <h3 class="text-xl font-semibold mb-4">9. Mudanças nesta Política</h3>
          <p>Reservamo-nos o direito de atualizar esta Política de Privacidade periodicamente. Informaremos sobre quaisquer alterações significativas através dos meios disponíveis, e recomendamos consulta regular a esta página.</p>
        </div>

        <div class="mb-4">
          <h3 class="text-xl font-semibold mb-4">10. Contato e Encarregado de Proteção de Dados (DPO)</h3>
          <p>Caso tenha dúvidas, preocupações ou queira exercer qualquer direito previsto nesta política, entre em contato conosco.</p>
        </div>

        <div class="border-t border-[var(--support-color)] pt-6 mt-8">
          <p>Ao utilizar nossos serviços, você declara ter lido, compreendido e aceitado integralmente os termos desta Política de Privacidade.</p>
          <p class="mt-2 text-sm text-[var(--support-color)]">Última atualização: <?= date('d/m/Y') ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('../inc/footer.php'); ?>
</body>
</html>